<?php

namespace App\Services;

use App\Models\VendorAccount;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PrestashopProductSyncService
{
    public function sync(VendorAccount $vendor): void
    {
        $vendor = $this->hydrateVendor($vendor);

        if (!$this->isCatalogReady($vendor)) {
            $this->disableForVendor($vendor);
            return;
        }

        if ($vendor->prestashop_product_id) {
            $this->updateForVendor($vendor);
            return;
        }

        $this->createForVendor($vendor);
    }

    public function createForVendor(VendorAccount $vendor): void
    {
        $vendor = $this->hydrateVendor($vendor);

        if (!$this->isCatalogReady($vendor)) {
            return;
        }

        $payload = $this->buildPayload($vendor);

        $response = $this->sendRequest('vendor-product-create', $payload);

        $productId = $response->json('product_id');

        if (!$productId) {
            throw new RuntimeException('Risposta PrestaShop senza product_id.');
        }

        $vendor->update([
            'prestashop_product_id' => (int) $productId,
        ]);
    }

    public function updateForVendor(VendorAccount $vendor): void
    {
        $vendor = $this->hydrateVendor($vendor);

        if (!$this->isCatalogReady($vendor)) {
            $this->disableForVendor($vendor);
            return;
        }

        if (!$vendor->prestashop_product_id) {
            $this->createForVendor($vendor);
            return;
        }

        $payload = $this->buildPayload($vendor);
        $payload['product_id'] = (int) $vendor->prestashop_product_id;

        $this->sendRequest('vendor-product-update', $payload);
    }

    public function disableForVendor(VendorAccount $vendor): void
    {
        $vendor = $this->hydrateVendor($vendor);

        if (!$vendor->prestashop_product_id) {
            return;
        }

        $this->sendRequest('vendor-product-disable', [
            'product_id' => (int) $vendor->prestashop_product_id,
        ]);
    }

    protected function hydrateVendor(VendorAccount $vendor): VendorAccount
    {
        $vendor->loadMissing([
            'category',
            'vendorOfferingProfiles' => function ($query) {
                $query->where('is_published', true)
                    ->with('images')
                    ->orderBy('id');
            },
        ]);

        return $vendor;
    }

    protected function isCatalogReady(VendorAccount $vendor): bool
    {
        if (($vendor->status ?? null) !== 'ACTIVE') {
            return false;
        }

        if (!$vendor->category || !$vendor->category->prestashop_category_id) {
            return false;
        }

        return $this->publishedProfiles($vendor)->isNotEmpty();
    }

    protected function buildPayload(VendorAccount $vendor): array
    {
        $categoryId = $this->resolvePrestashopCategoryId($vendor);
        $name = $this->resolveVendorName($vendor);
        $slug = $this->resolveVendorSlug($vendor, $name);

        $publishedProfiles = $this->publishedProfiles($vendor);
        $representativeProfile = $this->resolveRepresentativeProfile($vendor);

        $shortDescription = $this->buildShortDescription($vendor, $publishedProfiles, $representativeProfile);
        $description = $this->buildLongDescription($vendor, $publishedProfiles, $representativeProfile);

        $payload = [
            'vendor_id' => (int) $vendor->id,
            'name' => $name,
            'slug' => $slug,
            'description_short' => $shortDescription,
            'description' => $description,
            'active' => 1,
            'default_category_id' => $categoryId,
            'category_ids' => [$categoryId],
        ];

        $imageUrl = $this->resolveVendorCatalogImageUrl($vendor);

        if ($imageUrl !== null) {
            $payload['image_url'] = $imageUrl;
        }

        return $payload;
    }

    protected function publishedProfiles(VendorAccount $vendor): Collection
    {
        return collect($vendor->vendorOfferingProfiles)
            ->filter(fn ($profile) => (bool) $profile->is_published)
            ->values();
    }

    protected function resolveRepresentativeProfile(VendorAccount $vendor): mixed
    {
        $profiles = $this->publishedProfiles($vendor);

        if ($profiles->isEmpty()) {
            return null;
        }

        $withImage = $profiles->first(function ($profile) {
            return $this->resolveProfileImageUrl($profile) !== null;
        });

        return $withImage ?: $profiles->first();
    }

    protected function resolvePrestashopCategoryId(VendorAccount $vendor): int
    {
        $category = $vendor->category;

        if (!$category || !$category->prestashop_category_id) {
            throw new RuntimeException("Categoria PrestaShop mancante per vendor {$vendor->id}.");
        }

        return (int) $category->prestashop_category_id;
    }

    protected function resolveVendorName(VendorAccount $vendor): string
    {
        $name = trim($vendor->company_name ?: (($vendor->first_name ?? '') . ' ' . ($vendor->last_name ?? '')));

        return $name !== '' ? $name : 'Vendor ' . $vendor->id;
    }

    protected function resolveVendorSlug(VendorAccount $vendor, string $name): string
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = 'vendor';
        }

        return $slug . '-' . $vendor->id;
    }

    protected function buildShortDescription(VendorAccount $vendor, Collection $profiles, mixed $representativeProfile): string
    {
        $categoryName = trim((string) ($vendor->category->name ?? ''));
        $vendorName = $this->resolveVendorName($vendor);

        $titles = $profiles
            ->map(function ($profile) {
                return trim((string) ($profile->title ?? $profile->name ?? ''));
            })
            ->filter()
            ->take(3)
            ->implode(', ');

        if ($titles !== '') {
            $prefix = $categoryName !== '' ? $categoryName . ': ' : '';
            return $prefix . $titles;
        }

        if ($representativeProfile) {
            $short = trim((string) ($representativeProfile->short_description ?? ''));
            if ($short !== '') {
                return $short;
            }
        }

        if ($categoryName !== '') {
            return $vendorName . ' - servizi nella categoria ' . $categoryName;
        }

        return $vendorName;
    }

    protected function buildLongDescription(VendorAccount $vendor, Collection $profiles, mixed $representativeProfile): string
    {
        $vendorName = $this->resolveVendorName($vendor);
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

        $serviceLines = $profiles
            ->take(6)
            ->map(function ($profile) {
                $title = trim((string) ($profile->title ?? $profile->name ?? ''));
                $short = trim((string) ($profile->short_description ?? ''));
                $description = trim((string) ($profile->description ?? ''));

                if ($title === '' && $short === '' && $description === '') {
                    return null;
                }

                if ($title !== '' && $short !== '') {
                    return '- ' . $title . ': ' . $short;
                }

                if ($title !== '' && $description !== '') {
                    return '- ' . $title . ': ' . Str::limit(strip_tags($description), 220);
                }

                if ($title !== '') {
                    return '- ' . $title;
                }

                return '- ' . Str::limit(strip_tags($short !== '' ? $short : $description), 220);
            })
            ->filter()
            ->implode("\n");

        if ($serviceLines !== '') {
            return $intro . "\n\nServizi disponibili:\n" . $serviceLines;
        }

        if ($representativeProfile) {
            $description = trim((string) ($representativeProfile->description ?? ''));
            if ($description !== '') {
                return $intro . "\n\n" . $description;
            }

            $short = trim((string) ($representativeProfile->short_description ?? ''));
            if ($short !== '') {
                return $intro . "\n\n" . $short;
            }
        }

        return $intro;
    }

    protected function resolveVendorCatalogImageUrl(VendorAccount $vendor): ?string
    {
        if (!empty($vendor->profile_image_path)) {
            $path = ltrim((string) $vendor->profile_image_path, '/');
            return route('media.public', ['path' => $path]);
        }

        $profile = $this->resolveRepresentativeProfile($vendor);

        if (!$profile) {
            return null;
        }

        return $this->resolveProfileImageUrl($profile);
    }

    protected function resolveProfileImageUrl(mixed $profile): ?string
    {
        if (!$profile) {
            return null;
        }

        $imageUrl = $profile->cover_image_url ?? null;

        if (is_string($imageUrl) && trim($imageUrl) !== '') {
            return trim($imageUrl);
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

    protected function sendRequest(string $action, array $payload): Response
    {
        $endpoint = $this->resolveEndpoint();
        $apiKey = $this->resolveApiKey();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])->post(
            rtrim($endpoint, '/') . '?action=' . $action,
            $payload
        );

        if ($response->failed()) {
            throw new RuntimeException($this->buildRequestErrorMessage($action, $response));
        }

        return $response;
    }

    protected function resolveEndpoint(): string
    {
        $endpoint = trim((string) config('services.prestashop.endpoint'));

        if ($endpoint === '') {
            throw new RuntimeException('Endpoint PrestaShop non configurato.');
        }

        return $endpoint;
    }

    protected function resolveApiKey(): string
    {
        $apiKey = trim((string) config('services.prestashop.key'));

        if ($apiKey === '') {
            throw new RuntimeException('API key PrestaShop non configurata.');
        }

        return $apiKey;
    }

    protected function buildRequestErrorMessage(string $action, Response $response): string
    {
        $prefix = match ($action) {
            'vendor-product-create' => 'Creazione prodotto PrestaShop fallita',
            'vendor-product-update' => 'Aggiornamento prodotto PrestaShop fallito',
            'vendor-product-disable' => 'Disattivazione prodotto PrestaShop fallita',
            default => 'Richiesta PrestaShop fallita',
        };

        return $prefix . ': ' . $response->body();
    }
}