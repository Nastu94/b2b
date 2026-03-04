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

    // Metodo di ricerca che filtra vendor per offering, distanza e disponibilità slot
    public function search(array $params): array
    {
        // Estrazione e casting dei parametri di ricerca
        $offeringId = (int) $params['offering_id'];
        $date = $params['date'];
        $slotSlug = $params['slot_slug'];
        $lat = (float) $params['lat'];
        $lng = (float) $params['lng'];
        $radiusKm = (float) ($params['radius_km'] ?? 30);
        $limit = (int) ($params['limit'] ?? 30);

        // Formula Haversine per il calcolo della distanza geografica in MySQL
        $distanceSql = "
            (6371 * acos(
                cos(radians(?)) *
                cos(radians(COALESCE(operational_lat, legal_lat))) *
                cos(radians(COALESCE(operational_lng, legal_lng)) - radians(?)) +
                sin(radians(?)) *
                sin(radians(COALESCE(operational_lat, legal_lat)))
            ))
        ";

        // Query per ottenere vendor attivi con distanza entro il raggio specificato
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

        // Iterazione su ogni vendor trovato
        foreach ($vendors as $vendor) {

            // Ricerca dello slot specifico per il vendor
            $slot = VendorSlot::query()
                ->where('vendor_account_id', $vendor->id)
                ->where('slug', $slotSlug)
                ->where('is_active', true)
                ->first();

            // Se lo slot non esiste, salta al prossimo vendor
            if (!$slot) {
                continue;
            }

            // Controllo della disponibilità puntuale per la data richiesta
            $availability = $this->availability
                ->getAvailability($vendor->id, $date, $date);

            // Estrazione dei dati di disponibilità per la data
            $dayData = $availability[$date] ?? [];
            // Ricerca dello slot specifico nei dati di disponibilità
            $slotData = collect($dayData)
                ->firstWhere('vendor_slot_id', $slot->id);

            // Se lo slot non è disponibile, salta al prossimo vendor
            if (!$slotData || $slotData['status'] !== 'AVAILABLE') {
                continue;
            }

            // Aggiunta del vendor ai risultati con le informazioni rilevanti
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
