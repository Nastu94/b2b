<?php

namespace App\Services;

use App\Models\VendorAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class PrestashopVendorPayloadFactory
{
    public function buildPayload(VendorAccount $vendor, int $syncVersion): array
    {
        $publishedProfiles = collect($vendor->vendorOfferingProfiles)
            ->filter(fn ($profile) => (bool) $profile->is_published && (bool) $profile->is_approved)
            ->values();

        $representativeProfile = $publishedProfiles->first(function ($profile) {
            return !empty($profile->cover_image_url) || !empty($profile->cover_image_path);
        }) ?: $publishedProfiles->first();

        // Nome
        $companyName = trim((string) $vendor->company_name);
        $name = $companyName !== '' ? $companyName : trim((string) $vendor->first_name . ' ' . (string) $vendor->last_name);
        $name = $name ?: 'Vendor ' . $vendor->id;
        
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = 'vendor';
        }
        $slug .= '-' . $vendor->id;

        $categoryId = $vendor->category ? (int) $vendor->category->id : null;
        $categoryPrestashopId = $vendor->category ? (int) $vendor->category->prestashop_category_id : null;

        $shortDescription = $this->buildShortDescription($vendor, $publishedProfiles, $representativeProfile);
        $description = $this->buildLongDescription($vendor, $publishedProfiles, $representativeProfile);
        
        $payload = [
            'vendor_id' => (int) $vendor->id,
            'sync_version' => $syncVersion,
            'generated_at' => now()->toIso8601String(),
            'name' => $name,
            'slug' => $slug,
            'description_short' => $shortDescription,
            'description' => $description,
            'active' => (($vendor->status ?? null) === 'ACTIVE' && $publishedProfiles->isNotEmpty()) ? 1 : 0,
            
            // Per il webhook (mantiene dati strutturati)
            'category' => $vendor->category ? [
                'id' => $categoryId,
                'name' => (string) $vendor->category->name,
                'slug' => (string) $vendor->category->slug,
            ] : null,
            'city' => $vendor->effectiveCity(),
            'address' => $vendor->operational_address_line1 ?: $vendor->legal_address_line1,
            'phone' => $vendor->phone,
            'cover_url' => $this->resolveVendorCoverUrl($vendor, $representativeProfile),
            'offerings_count' => $publishedProfiles->count(),
            'offerings' => $publishedProfiles->map(function ($profile) {
                return [
                    'id' => (int) $profile->id,
                    'offering_id' => (int) $profile->offering_id,
                    'title' => trim((string) ($profile->title ?? 'Servizio')),
                    'short_description' => (string) ($profile->short_description ?: ''),
                    'description' => (string) ($profile->description ?: ''),
                    'cover_image_url' => $this->resolveOfferingCoverUrl($profile),
                    'service_mode' => (string) $profile->service_mode,
                    'service_radius_km' => $profile->service_radius_km !== null ? (float) $profile->service_radius_km : null,
                    'max_guests' => $profile->max_guests !== null ? (int) $profile->max_guests : null,
                ];
            })->toArray(),
        ];
        
        // Aggiungo campi necessari alla API PrestashopProductSyncService
        if ($categoryPrestashopId) {
            $payload['default_category_id'] = $categoryPrestashopId;
            $payload['category_ids'] = [$categoryPrestashopId];
        }
        
        if ($payload['cover_url']) {
            $payload['image_url'] = $payload['cover_url'];
        }

        if ($vendor->prestashop_product_id) {
            $payload['id_product'] = (int) $vendor->prestashop_product_id;
            $payload['product_id'] = (int) $vendor->prestashop_product_id;
        }

        return $payload;
    }

    protected function resolveVendorCoverUrl(VendorAccount $vendor, $representativeProfile = null): ?string
    {
        if (!empty($vendor->profile_image_path)) {
            $path = ltrim((string) $vendor->profile_image_path, '/');
            return route('media.public', ['path' => $path]);
        }
        return $this->resolveOfferingCoverUrl($representativeProfile);
    }

    protected function resolveOfferingCoverUrl($profile): ?string
    {
        if (!$profile) return null;
        if (!empty($profile->cover_image_url)) return (string) $profile->cover_image_url;
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

    protected function buildShortDescription(VendorAccount $vendor, Collection $profiles, mixed $representativeProfile): string
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

    protected function buildLongDescription(VendorAccount $vendor, Collection $profiles, mixed $representativeProfile): string
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
