<?php

namespace App\Livewire\Vendor\Dashboard;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.vendor')]
class VendorDashboardPage extends Component
{
    use AuthorizesRequests;

    public string $vendorName = '';
    public string $status = 'N/A';
    public string $categoryName = 'N/A';

    /** @var array<int, array{id:int, name:string}> */
    public array $activeOfferings = [];

    // Riepilogo disponibilità
    public int   $slotsCount          = 0;
    public int   $openDaysCount       = 0;
    public array $openDayNames        = [];
    public bool  $leadTimeConfigured  = false;
    public array $upcomingBlackouts   = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        // Gating: il pannello vendor richiede vendor.access
        abort_unless($user->can('vendor.access'), 403);

        $vendorAccount = $user->vendorAccount;

        // Difesa in profondità: evita null e vendor soft-deleted
        if (!$vendorAccount || $vendorAccount->trashed()) {
            abort(403, 'Vendor account non disponibile o disattivato.');
        }

        // Coerenza con le policy: il vendor può vedere l'area "profili" solo se viewAny è permesso.
        // Qui non stiamo aggiornando profili, stiamo solo preparando la dashboard.
        $this->authorize('viewAny', \App\Models\VendorOfferingProfile::class);

        // Dati base del vendor
        $this->vendorName = $user->name;
        $this->status = $vendorAccount->status ?? 'N/A';
        $this->categoryName = $vendorAccount->category?->name ?? 'N/A';

        $this->activeOfferings = [];

        $vendorId = $vendorAccount->id;

        $offerings = $vendorAccount->offerings()
            ->wherePivot('is_active', true)
            ->orderBy('offerings.name')
            ->get(['offerings.id', 'offerings.name']);

        // Carico tutti i profili del vendor per quelle offerings in 1 query
        $profiles = \App\Models\VendorOfferingProfile::query()
            ->where('vendor_account_id', $vendorId)
            ->whereIn('offering_id', $offerings->pluck('id'))
            ->withCount('images')
            ->get()
            ->keyBy('offering_id');

        $this->activeOfferings = $offerings->map(function ($o) use ($profiles) {
            $p = $profiles->get($o->id);

            $hasCover = !empty($p?->cover_image_path);
            $hasDesc  = !empty($p?->description);
            $isPublished = (bool) ($p?->is_published);

            $statusLabel = $isPublished
                ? 'PUBBLICATO'
                : (($hasCover || $hasDesc) ? 'IN BOZZA' : 'INCOMPLETO');

            return [
                'id' => (int) $o->id,
                'name' => (string) $o->name,

                // profilo contenuti
                'title' => (string) ($p?->title ?? ''),
                'short_description' => (string) ($p?->short_description ?? ''),
                'description' => (string) ($p?->description ?? ''),
                'cover_image_path' => (string) ($p?->cover_image_path ?? ''),
                'images_count' => (int) ($p?->images_count ?? 0),

                // stato
                'is_published' => $isPublished,
                'status_label' => $statusLabel,
            ];
        })->all();

        // Riepilogo disponibilità 

        // Quanti slot ha configurato il vendor
        $this->slotsCount = $vendorAccount->slots()->active()->count();

        // Giorni aperti nel template settimanale (distinti)
        $dayNames = [
            0 => 'Domenica', 1 => 'Lunedi',    2 => 'Martedi',
            3 => 'Mercoledi', 4 => 'Giovedi', 5 => 'Venerdi', 6 => 'Sabato',
        ];

        $openDays = $vendorAccount->weeklySchedules()
            ->where('is_open', true)
            ->pluck('day_of_week')
            ->unique()
            ->sort()
            ->values();

        $this->openDaysCount = $openDays->count();
        $this->openDayNames  = $openDays->map(fn($d) => $dayNames[$d] ?? '?')->toArray();

        // Lead time: configurato se esiste almeno un record
        $this->leadTimeConfigured = $vendorAccount->leadTimes()->exists();

        // Prossimi blackout attivi (max 3)
        $this->upcomingBlackouts = $vendorAccount->blackouts()
            ->where('date_to', '>=', now()->toDateString())
            ->orderBy('date_from')
            ->limit(3)
            ->get()
            ->map(fn($b) => [
                'range'    => $b->date_from->format('d/m/Y') === $b->date_to->format('d/m/Y')
                    ? $b->date_from->format('d/m/Y')
                    : $b->date_from->format('d/m/Y') . ' - ' . $b->date_to->format('d/m/Y'),
                'reason'   => $b->reason_internal ?? 'Nessun motivo specificato',
                'full_day' => is_null($b->vendor_slot_id),
            ])
            ->toArray();
    }

    public function render()
    {
        // Passiamo "title" al layout vendor
        return view('livewire.vendor.dashboard.vendor-dashboard-page', [
            'title' => 'Dashboard',
        ]);
    }
}