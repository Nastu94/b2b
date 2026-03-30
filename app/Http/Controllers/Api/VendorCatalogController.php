<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\VendorAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VendorCatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = isset($validated['q']) ? trim((string) $validated['q']) : '';
        $categoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 24;

        $vendorsQuery = VendorAccount::query()
            ->whereNull('deleted_at')
            ->where('status', 'ACTIVE')
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->whereHas('vendorOfferingProfiles', function ($query) {
                $query->where('is_published', true);
            })
            ->with([
                'category:id,name,slug,is_active',
                'vendorOfferingProfiles' => function ($query) {
                    $query->select([
                        'id',
                        'vendor_account_id',
                        'offering_id',
                        'title',
                        'short_description',
                        'description',
                        'cover_image_path',
                        'service_mode',
                        'service_radius_km',
                        'max_guests',
                        'is_published',
                    ])
                    ->where('is_published', true)
                    ->orderBy('id');
                },
            ]);

        if ($categoryId) {
            $vendorsQuery->where('category_id', $categoryId);
        }

        if ($q !== '') {
            $vendorsQuery->where(function ($query) use ($q) {
                $query->where('company_name', 'like', '%' . $q . '%')
                    ->orWhere('first_name', 'like', '%' . $q . '%')
                    ->orWhere('last_name', 'like', '%' . $q . '%')
                    ->orWhereHas('vendorOfferingProfiles', function ($profilesQuery) use ($q) {
                        $profilesQuery->where('is_published', true)
                            ->where(function ($innerQuery) use ($q) {
                                $innerQuery->where('title', 'like', '%' . $q . '%')
                                    ->orWhere('short_description', 'like', '%' . $q . '%')
                                    ->orWhere('description', 'like', '%' . $q . '%');
                            });
                    });
            });
        }

        $vendors = $vendorsQuery
            ->orderByRaw('COALESCE(company_name, last_name, first_name) asc')
            ->limit($limit)
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('vendorAccounts', function ($query) {
                $query->whereNull('deleted_at')
                    ->where('status', 'ACTIVE')
                    ->whereHas('vendorOfferingProfiles', function ($profilesQuery) {
                        $profilesQuery->where('is_published', true);
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'success' => true,
            'data' => [
                'filters' => [
                    'q' => $q,
                    'category_id' => $categoryId,
                ],
                'categories' => $categories->map(function (Category $category) {
                    return [
                        'id' => (int) $category->id,
                        'name' => (string) $category->name,
                        'slug' => (string) $category->slug,
                    ];
                })->values(),
                'vendors' => $vendors->map(function (VendorAccount $vendor) {
                    return $this->mapVendorCard($vendor);
                })->values(),
            ],
        ]);
    }

    public function showByProduct(int $idProduct): JsonResponse
    {
        if ($idProduct <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Prodotto non valido.',
            ], 422);
        }

        $vendor = VendorAccount::query()
            ->whereNull('deleted_at')
            ->where('status', 'ACTIVE')
            ->where('prestashop_product_id', $idProduct)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->whereHas('vendorOfferingProfiles', function ($query) {
                $query->where('is_published', true);
            })
            ->with([
                'category:id,name,slug,is_active',
                'vendorOfferingProfiles' => function ($query) {
                    $query->select([
                        'id',
                        'vendor_account_id',
                        'offering_id',
                        'title',
                        'short_description',
                        'description',
                        'cover_image_path',
                        'service_mode',
                        'service_radius_km',
                        'max_guests',
                        'is_published',
                    ])
                    ->where('is_published', true)
                    ->orderBy('id');
                },
            ])
            ->first();

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor non trovato.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapVendorByProductDetail($vendor),
        ]);
    }

    public function show(Request $request, ?string $vendor = null): JsonResponse
    {
        // Supporto retrocompatibile (PrestaShop legacy)
        $vendorId = $request->query('vendor_id');
        $slug = $request->query('slug');

        // Se passiamo il parametro nella route (/vendors/{vendor}) esso prende priorità
        if ($vendor !== null) {
            if (is_numeric($vendor)) {
                $vendorId = (int) $vendor;
            } else {
                $slug = $vendor;
            }
        }

        if (!$vendorId && !$slug) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor non specificato.',
            ], 422);
        }

        $query = VendorAccount::query()
            ->whereNull('deleted_at')
            ->where('status', 'ACTIVE')
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->whereHas('vendorOfferingProfiles', function ($query) {
                $query->where('is_published', true);
            })
            ->with([
                'category:id,name,slug,is_active',
                'vendorOfferingProfiles' => function ($query) {
                    $query->select([
                        'id',
                        'vendor_account_id',
                        'offering_id',
                        'title',
                        'short_description',
                        'description',
                        'cover_image_path',
                        'service_mode',
                        'service_radius_km',
                        'max_guests',
                        'is_published',
                    ])
                    ->where('is_published', true)
                    ->orderBy('id');
                },
            ]);

        $vendorModel = null;

        if ($vendorId) {
            // Ricerca diretta O(1)
            $vendorModel = (clone $query)->where('id', $vendorId)->first();
        } elseif ($slug) {
            // Parsing robusto del suffisso numerico dallo slug
            if (preg_match('/-(\d+)$/', $slug, $matches)) {
                $idFromSlug = (int) $matches[1];
                $vendorModel = (clone $query)->where('id', $idFromSlug)->first();
                
                // Sicurezza anti-regressione/spoofing: validiamo che quello caricato 
                // abbia davvero lo stesso slug calcolato (previene orfani o slug mixati)
                if ($vendorModel && $this->vendorSlug($vendorModel) !== $slug) {
                    $vendorModel = null;
                }
            }
        }

        if (!$vendorModel) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor non trovato.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapVendorDetail($vendorModel),
        ]);
    }

    protected function mapVendorCard(VendorAccount $vendor): array
    {
        $publishedProfiles = $this->publishedProfiles($vendor);
        $representativeProfile = $this->representativeProfile($vendor);

        return [
            'id' => (int) $vendor->id,
            'slug' => $this->vendorSlug($vendor),
            'name' => $this->vendorName($vendor),
            'category' => $vendor->category ? [
                'id' => (int) $vendor->category->id,
                'name' => (string) $vendor->category->name,
                'slug' => (string) $vendor->category->slug,
            ] : null,
            'city' => $vendor->effectiveCity(),
            'phone' => $vendor->phone,
            'cover_image_url' => $this->profileCoverImageUrl($representativeProfile),
            'short_description' => $this->vendorShortDescription($vendor, $publishedProfiles, $representativeProfile),
            'offerings_count' => $publishedProfiles->count(),
        ];
    }

    protected function mapVendorDetail(VendorAccount $vendor): array
    {
        $publishedProfiles = $this->publishedProfiles($vendor);
        $representativeProfile = $this->representativeProfile($vendor);

        return [
            'id' => (int) $vendor->id,
            'slug' => $this->vendorSlug($vendor),
            'name' => $this->vendorName($vendor),
            'category' => $vendor->category ? [
                'id' => (int) $vendor->category->id,
                'name' => (string) $vendor->category->name,
                'slug' => (string) $vendor->category->slug,
            ] : null,
            'city' => $vendor->effectiveCity(),
            'phone' => $vendor->phone,
            'cover_image_url' => $this->profileCoverImageUrl($representativeProfile),
            'description' => $this->vendorLongDescription($vendor, $publishedProfiles, $representativeProfile),
            'offerings' => $publishedProfiles->map(function ($profile) {
                return [
                    'id' => (int) $profile->id,
                    'offering_id' => (int) $profile->offering_id,
                    'title' => $this->profileTitle($profile),
                    'short_description' => (string) ($profile->short_description ?: ''),
                    'description' => (string) ($profile->description ?: ''),
                    'cover_image_url' => $this->profileCoverImageUrl($profile),
                    'service_mode' => (string) $profile->service_mode,
                    'service_radius_km' => $profile->service_radius_km !== null ? (float) $profile->service_radius_km : null,
                    'max_guests' => $profile->max_guests !== null ? (int) $profile->max_guests : null,
                ];
            })->values(),
        ];
    }

    protected function mapVendorByProductDetail(VendorAccount $vendor): array
    {
        $publishedProfiles = $this->publishedProfiles($vendor);
        $representativeProfile = $this->representativeProfile($vendor);

        return [
            'id' => (int) $vendor->id,
            'prestashop_product_id' => $vendor->prestashop_product_id !== null ? (int) $vendor->prestashop_product_id : null,
            'slug' => $this->vendorSlug($vendor),
            'name' => $this->vendorName($vendor),
            'category' => $vendor->category ? [
                'id' => (int) $vendor->category->id,
                'name' => (string) $vendor->category->name,
                'slug' => (string) $vendor->category->slug,
            ] : null,
            'city' => $vendor->effectiveCity(),
            'phone' => $vendor->phone,
            'cover_url' => $this->profileCoverImageUrl($representativeProfile),
            'short_description' => $this->vendorShortDescription($vendor, $publishedProfiles, $representativeProfile),
            'description' => $this->vendorLongDescription($vendor, $publishedProfiles, $representativeProfile),
            'offerings_count' => $publishedProfiles->count(),
            'offerings' => $publishedProfiles->map(function ($profile) {
                return [
                    'id' => (int) $profile->id,
                    'offering_id' => (int) $profile->offering_id,
                    'title' => $this->profileTitle($profile),
                    'short_description' => (string) ($profile->short_description ?: ''),
                    'description' => (string) ($profile->description ?: ''),
                    'cover_image_url' => $this->profileCoverImageUrl($profile),
                    'service_mode' => (string) $profile->service_mode,
                    'service_radius_km' => $profile->service_radius_km !== null ? (float) $profile->service_radius_km : null,
                    'max_guests' => $profile->max_guests !== null ? (int) $profile->max_guests : null,
                ];
            })->values(),
        ];
    }

    protected function publishedProfiles(VendorAccount $vendor): Collection
    {
        return collect($vendor->vendorOfferingProfiles)
            ->filter(fn ($profile) => (bool) $profile->is_published)
            ->values();
    }

    protected function representativeProfile(VendorAccount $vendor): mixed
    {
        $publishedProfiles = $this->publishedProfiles($vendor);

        if ($publishedProfiles->isEmpty()) {
            return null;
        }

        $withCover = $publishedProfiles->first(function ($profile) {
            return $this->profileCoverImageUrl($profile) !== null;
        });

        return $withCover ?: $publishedProfiles->first();
    }

    protected function vendorName(VendorAccount $vendor): string
    {
        $companyName = trim((string) $vendor->company_name);

        if ($companyName !== '') {
            return $companyName;
        }

        $fullName = trim(implode(' ', array_filter([
            trim((string) $vendor->first_name),
            trim((string) $vendor->last_name),
        ])));

        return $fullName !== '' ? $fullName : 'Vendor';
    }

    protected function vendorSlug(VendorAccount $vendor): string
    {
        $base = Str::slug($this->vendorName($vendor));

        if ($base === '') {
            $base = 'vendor';
        }

        return $base . '-' . $vendor->id;
    }

    protected function vendorShortDescription(VendorAccount $vendor, Collection $publishedProfiles, mixed $representativeProfile): string
    {
        $titles = $publishedProfiles
            ->map(function ($profile) {
                return trim((string) ($profile->title ?? ''));
            })
            ->filter()
            ->take(3)
            ->implode(', ');

        if ($titles !== '') {
            $category = trim((string) ($vendor->category?->name ?? ''));
            if ($category !== '') {
                return $category . ': ' . $titles;
            }

            return $titles;
        }

        if ($representativeProfile && trim((string) $representativeProfile->short_description) !== '') {
            return (string) $representativeProfile->short_description;
        }

        $parts = array_filter([
            $vendor->category?->name,
            $vendor->effectiveCity(),
        ]);

        return !empty($parts) ? implode(' · ', $parts) : 'Scopri i servizi disponibili di questo vendor.';
    }

    protected function vendorLongDescription(VendorAccount $vendor, Collection $publishedProfiles, mixed $representativeProfile): string
    {
        $vendorName = $this->vendorName($vendor);
        $categoryName = trim((string) ($vendor->category?->name ?? ''));
        $serviceCount = $publishedProfiles->count();

        $introParts = [$vendorName];

        if ($categoryName !== '') {
            $introParts[] = 'opera nella categoria ' . $categoryName;
        }

        if ($serviceCount > 0) {
            $introParts[] = 'e propone ' . $serviceCount . ' servizi pubblicati';
        }

        $intro = rtrim(implode(' ', $introParts), '. ') . '.';

        $serviceLines = $publishedProfiles
            ->take(6)
            ->map(function ($profile) {
                $title = trim((string) ($profile->title ?? ''));
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

        if ($representativeProfile && trim((string) $representativeProfile->description) !== '') {
            return $intro . "\n\n" . (string) $representativeProfile->description;
        }

        if ($representativeProfile && trim((string) $representativeProfile->short_description) !== '') {
            return $intro . "\n\n" . (string) $representativeProfile->short_description;
        }

        return $intro !== '' ? $intro : 'Scopri i servizi disponibili di questo vendor.';
    }

    protected function profileTitle($profile): string
    {
        $title = trim((string) ($profile->title ?? ''));

        if ($title !== '') {
            return $title;
        }

        return 'Servizio';
    }

    protected function profileCoverImageUrl($profile): ?string
    {
        if (!$profile) {
            return null;
        }

        if (!empty($profile->cover_image_url)) {
            return (string) $profile->cover_image_url;
        }

        if (!empty($profile->cover_image_path)) {
            return (string) $profile->cover_image_path;
        }

        return null;
    }
}