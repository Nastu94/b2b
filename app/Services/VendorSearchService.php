<?php

namespace App\Services;

use App\Models\VendorAccount;
use App\Models\VendorSlot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class VendorSearchService
{
    protected AvailabilityService $availability;

    public function __construct(AvailabilityService $availability)
    {
        $this->availability = $availability;
    }

    public function search(array $params): array
    {
        $offeringId = (int) $params['offering_id'];
        $date = $params['date'];
        $slotSlug = $params['slot_slug'];
        $lat = (float) $params['lat'];
        $lng = (float) $params['lng'];
        $radiusKm = (float) ($params['radius_km'] ?? 30);
        $limit = (int) ($params['limit'] ?? 30);

        // Formula Haversine MySQL
        $distanceSql = "
            (6371 * acos(
                cos(radians(?)) *
                cos(radians(COALESCE(operational_lat, legal_lat))) *
                cos(radians(COALESCE(operational_lng, legal_lng)) - radians(?)) +
                sin(radians(?)) *
                sin(radians(COALESCE(operational_lat, legal_lat)))
            ))
        ";

        $vendors = VendorAccount::query()
            ->select([
                'vendor_accounts.*',
                DB::raw("$distanceSql AS distance_km")
            ])
            ->addBinding([$lat, $lng, $lat], 'select')
            ->where('status', 'ACTIVE')
            ->whereNotNull(DB::raw('COALESCE(operational_lat, legal_lat)'))
            ->whereHas('offerings', function ($q) use ($offeringId) {
                $q->where('offering_id', $offeringId)
                    ->where('offerings.is_active', 1)
                    ->where('vendor_offerings.is_active', 1);
            })
            ->whereHas('offeringProfiles', function ($q) use ($offeringId) {
                $q->where('offering_id', $offeringId)
                    ->where('is_published', true);
            })
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km')
            ->limit($limit)
            ->get();

        $results = [];

        foreach ($vendors as $vendor) {

            $slot = VendorSlot::query()
                ->where('vendor_account_id', $vendor->id)
                ->where('slug', $slotSlug)
                ->where('is_active', true)
                ->first();

            if (!$slot) {
                continue;
            }

            // check disponibilità puntuale
            $availability = $this->availability
                ->getAvailability($vendor->id, $date, $date);

            $dayData = $availability[$date] ?? [];
            $slotData = collect($dayData)
                ->firstWhere('vendor_slot_id', $slot->id);

            if (!$slotData || $slotData['status'] !== 'AVAILABLE') {
                continue;
            }

            $results[] = [
                'vendor_account_id' => $vendor->id,
                'company_name' => $vendor->company_name,
                'distance_km' => round($vendor->distance_km, 2),
                'slot' => [
                    'slug' => $slot->slug,
                    'label' => $slot->label,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                ],
            ];
        }

        return $results;
    }
}
