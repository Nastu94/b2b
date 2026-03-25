<?php

namespace App\Services;

use App\Models\VendorAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PrestashopWebhookService
{
    public function pushVendor(VendorAccount $vendor): bool
    {
        // 1. Non possiamo sincronizzare se non abbiamo il legame con PrestaShop
        if (!$vendor->prestashop_product_id) {
            return false;
        }

        // 2. Se non è attivo o è eliminato, il webhook svuoterà/aggiornerà i dati base
        // ma noi controlliamo comunque le logiche.
        
        $vendor->loadMissing([
            'category',
            'vendorOfferingProfiles' => function ($query) {
                $query->where('is_published', true)->orderBy('id');
            },
        ]);

        $payload = $this->buildPayload($vendor);

        // 3. Determina l'URL del webhook
        // Di default prendiamo services.prestashop.webhook_url, o costruiamo da config app
        $webhookUrl = config('services.prestashop.webhook_url');
        if (empty($webhookUrl)) {
            $base = rtrim(config('services.prestashop.endpoint', config('app.url')), '/');
            // Rimuoviamo eventuali suffissi dell'endpoint API dalla stringa base
            $base = preg_replace('#/module/bookingbridge(?:/api)?/?$#', '', $base);
            $base = preg_replace('#/api/?$#', '', $base);
            $base = rtrim($base, '/');
            
            $webhookUrl = $base . '/module/bookingbridge/webhook';
        }

        $apiKey = config('services.prestashop.webhook_key', config('services.prestashop.key', ''));

        if (empty($apiKey) || empty($webhookUrl)) {
            Log::warning('PrestaShop Webhook config missing (URL or Key).');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'X-Booking-Bridge-Key' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($webhookUrl, $payload);

            if ($response->failed()) {
                throw new \Exception("URL chiamato ($webhookUrl) ha risposto con Errore {$response->status()}: " . strip_tags($response->body()));
            }

            return true;
        } catch (\Exception $e) {
            Log::error('PrestaShop Webhook Exception: ' . $e->getMessage());
            // Rilanciamo l'eccezione così l'Artisan Command "sincrono" la stamperà in console per fare debug
            throw $e;
        }
    }

    protected function buildPayload(VendorAccount $vendor): array
    {
        $publishedProfiles = collect($vendor->vendorOfferingProfiles)
            ->filter(fn ($profile) => (bool) $profile->is_published)
            ->values();

        $representativeProfile = $publishedProfiles->first(function ($profile) {
            return !empty($profile->cover_image_url) || !empty($profile->cover_image_path);
        }) ?: $publishedProfiles->first();

        // Nome
        $companyName = trim((string) $vendor->company_name);
        $name = $companyName !== '' ? $companyName : trim((string) $vendor->first_name . ' ' . (string) $vendor->last_name);
        $name = $name ?: 'Vendor';

        return [
            'id' => (int) $vendor->id,
            'id_product' => (int) $vendor->prestashop_product_id,
            'name' => $name,
            'category' => $vendor->category ? [
                'id' => (int) $vendor->category->id,
                'name' => (string) $vendor->category->name,
                'slug' => (string) $vendor->category->slug,
            ] : null,
            'city' => $vendor->effectiveCity(),
            'address' => $vendor->operational_address_line1 ?: $vendor->legal_address_line1,
            'phone' => $vendor->phone,
            'cover_url' => $this->resolveVendorCoverUrl($vendor, $representativeProfile),
            'short_description' => $this->buildShortDescription($vendor, $publishedProfiles, $representativeProfile),
            'description' => $this->buildLongDescription($vendor, $publishedProfiles, $representativeProfile),
            'offerings_count' => $publishedProfiles->count(),
            'offerings' => $publishedProfiles->map(function ($profile) use ($vendor) {
                return [
                    'id' => (int) $profile->id,
                    'offering_id' => (int) $profile->offering_id,
                    'title' => trim((string) ($profile->title ?? 'Servizio')),
                    'short_description' => (string) ($profile->short_description ?: ''),
                    'description' => (string) ($profile->description ?: ''),
                    'cover_image_url' => $this->resolveOfferingCoverUrl($profile) ?: route('media.public', ['path' => 'placeholder']), // Fallback url se route esiste
                    'service_mode' => (string) $profile->service_mode,
                    'service_radius_km' => $profile->service_radius_km !== null ? (float) $profile->service_radius_km : null,
                    'max_guests' => $profile->max_guests !== null ? (int) $profile->max_guests : null,
                ];
            })->toArray(),
        ];
    }

    protected function resolveVendorCoverUrl(\App\Models\VendorAccount $vendor, $representativeProfile = null): ?string
    {
        // Se il fornitore ha caricato un'immagine di profilo (logo/copertina principale)
        if (!empty($vendor->profile_image_path)) {
            $path = ltrim((string) $vendor->profile_image_path, '/');
            return route('media.public', ['path' => $path]);
        }

        return $this->resolveOfferingCoverUrl($representativeProfile);
    }

    protected function resolveOfferingCoverUrl($profile): ?string
    {
        if (!$profile) {
            return null;
        }

        if (!empty($profile->cover_image_url)) {
            return (string) $profile->cover_image_url;
        }

        if (!empty($profile->cover_image_path)) {
            $path = ltrim((string) $profile->cover_image_path, '/');
            return route('media.public', ['path' => $path]);
        }

        if ($profile->relationLoaded('images') && $profile->images->isNotEmpty()) {
            $firstImage = $profile->images->first();
            if (!empty($firstImage->path)) {
                $path = ltrim((string) $firstImage->path, '/');
                return route('media.public', ['path' => $path]);
            }
        }

        return null;
    }

    protected function buildShortDescription(VendorAccount $vendor, \Illuminate\Support\Collection $profiles, mixed $representativeProfile): string
    {
        $categoryName = trim((string) ($vendor->category->name ?? ''));
        $companyName = trim((string) $vendor->company_name);
        $vendorName = $companyName !== '' ? $companyName : trim((string) $vendor->first_name . ' ' . (string) $vendor->last_name);

        $titles = $profiles
            ->map(function ($profile) {
                return trim((string) ($profile->title ?? $profile->name ?? ''));
            })->filter()->take(3)->implode(', ');

        if ($titles !== '') {
            $prefix = $categoryName !== '' ? $categoryName . ': ' : '';
            return $prefix . $titles;
        }

        if ($representativeProfile && trim((string) ($representativeProfile->short_description ?? '')) !== '') {
            return (string) $representativeProfile->short_description;
        }

        return $vendorName . ($categoryName !== '' ? ' - servizi nella categoria ' . $categoryName : '');
    }

    protected function buildLongDescription(VendorAccount $vendor, \Illuminate\Support\Collection $profiles, mixed $representativeProfile): string
    {
        $companyName = trim((string) $vendor->company_name);
        $vendorName = $companyName !== '' ? $companyName : trim((string) $vendor->first_name . ' ' . (string) $vendor->last_name);
        $categoryName = trim((string) ($vendor->category->name ?? ''));
        $serviceCount = $profiles->count();

        $introParts = [$vendorName];
        if ($categoryName !== '') {
            $introParts[] = 'opera nella categoria ' . $categoryName;
        }
        if ($serviceCount > 0) {
            $introParts[] = 'e propone ' . $serviceCount . ' servizi pubblicati';
        }
        $intro = rtrim(implode(' ', $introParts), '. ') . '.';

        $serviceLines = $profiles->take(6)->map(function ($profile) {
            $title = trim((string) ($profile->title ?? $profile->name ?? ''));
            $short = trim((string) ($profile->short_description ?? ''));
            $description = trim((string) ($profile->description ?? ''));

            if ($title === '' && $short === '' && $description === '') return null;
            if ($title !== '' && $short !== '') return '- ' . $title . ': ' . $short;
            if ($title !== '' && $description !== '') return '- ' . $title . ': ' . Str::limit(strip_tags($description), 220);
            if ($title !== '') return '- ' . $title;
            return '- ' . Str::limit(strip_tags($short !== '' ? $short : $description), 220);
        })->filter()->implode("\n");

        if ($serviceLines !== '') {
            return $intro . "\n\nServizi disponibili:\n" . $serviceLines;
        }

        if ($representativeProfile) {
            $description = trim((string) ($representativeProfile->description ?? ''));
            if ($description !== '') return $intro . "\n\n" . $description;
            $short = trim((string) ($representativeProfile->short_description ?? ''));
            if ($short !== '') return $intro . "\n\n" . $short;
        }

        return $intro;
    }
}
