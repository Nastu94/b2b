<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\VendorAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => ['nullable', 'integer'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $vendorId = isset($validated['vendor_id']) ? (int) $validated['vendor_id'] : null;
        $slug = isset($validated['slug']) ? trim((string) $validated['slug']) : null;

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

        if ($vendorId) {
            $query->where('id', $vendorId);
        } else {
            $all = $query->get();

            $vendor = $all->first(function (VendorAccount $item) use ($slug) {
                return $this->vendorSlug($item) === $slug;
            });

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor non trovato.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->mapVendorDetail($vendor),
            ]);
        }

        $vendor = $query->first();

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor non trovato.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapVendorDetail($vendor),
        ]);
    }

    protected function mapVendorCard(VendorAccount $vendor): array
    {
        $publishedProfiles = $vendor->vendorOfferingProfiles
            ->where('is_published', true)
            ->values();

        $firstProfile = $publishedProfiles->first();

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
            'cover_image_url' => $this->profileCoverImageUrl($firstProfile),
            'short_description' => $this->vendorShortDescription($vendor, $firstProfile),
            'offerings_count' => $publishedProfiles->count(),
        ];
    }

    protected function mapVendorDetail(VendorAccount $vendor): array
    {
        $publishedProfiles = $vendor->vendorOfferingProfiles
            ->where('is_published', true)
            ->values();

        $firstProfile = $publishedProfiles->first();

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
            'cover_image_url' => $this->profileCoverImageUrl($firstProfile),
            'description' => $this->vendorLongDescription($vendor, $firstProfile),
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

    protected function vendorShortDescription(VendorAccount $vendor, $firstProfile): string
    {
        if ($firstProfile && trim((string) $firstProfile->short_description) !== '') {
            return (string) $firstProfile->short_description;
        }

        $parts = array_filter([
            $vendor->category?->name,
            $vendor->effectiveCity(),
        ]);

        return !empty($parts) ? implode(' · ', $parts) : 'Scopri i servizi disponibili di questo vendor.';
    }

    protected function vendorLongDescription(VendorAccount $vendor, $firstProfile): string
    {
        if ($firstProfile && trim((string) $firstProfile->description) !== '') {
            return (string) $firstProfile->description;
        }

        if ($firstProfile && trim((string) $firstProfile->short_description) !== '') {
            return (string) $firstProfile->short_description;
        }

        return 'Scopri i servizi disponibili di questo vendor.';
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