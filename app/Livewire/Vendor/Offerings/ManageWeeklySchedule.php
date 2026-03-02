<?php

namespace App\Livewire\Vendor\Offerings;

use App\Models\VendorSlot;
use App\Models\VendorWeeklySchedule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ManageWeeklySchedule extends Component
{
    use AuthorizesRequests;

    public array $days = [
        1 => 'Lunedì',
        2 => 'Martedì',
        3 => 'Mercoledì',
        4 => 'Giovedì',
        5 => 'Venerdì',
        6 => 'Sabato',
        0 => 'Domenica',
    ];

    public $slots;
    public array $schedule = [];

    public function mount(): void
    {
        $this->authorize('viewAny', VendorWeeklySchedule::class);
        $this->loadSchedule();
    }

    private function loadSchedule(): void
    {
        $vendorAccount = Auth::user()->vendorAccount;

        $this->slots = $vendorAccount
            ->slots()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        // Inizializza tutti i giorni con valori default
        $this->schedule = [];
        foreach ($this->slots as $slot) {
            foreach (array_keys($this->days) as $day) {
                $this->schedule[$slot->id][$day] = [
                    'is_open'          => false,
                    'min_notice_hours' => 48,
                    'cutoff_time'      => '',
                ];
            }
        }

        // Sovrascrive con i valori salvati nel DB
        $saved = $vendorAccount->weeklySchedules()->get();

        foreach ($saved as $row) {
            $this->schedule[$row->vendor_slot_id][$row->day_of_week] = [
                'is_open'          => (bool) $row->is_open,
                'min_notice_hours' => (int)  $row->min_notice_hours,
                'cutoff_time'      => $row->cutoff_time
                    ? substr($row->cutoff_time, 0, 5)
                    : '',
            ];
        }
    }

    public function save(): void
    {
        $this->authorize('create', VendorWeeklySchedule::class);

        $vendorAccount = Auth::user()->vendorAccount;

        // Solo slot autorizzati per questo vendor
        $allowedSlotIds = $vendorAccount
            ->slots()
            ->active()
            ->pluck('id')
            ->toArray();

        foreach ($this->schedule as $slotId => $days) {
            if (!in_array((int) $slotId, $allowedSlotIds, true)) {
                continue;
            }

            foreach ($days as $day => $values) {
                $day = (int) $day;
                if ($day < 0 || $day > 6) continue;

                VendorWeeklySchedule::updateOrCreate(
                    [
                        'vendor_account_id' => $vendorAccount->id,
                        'vendor_slot_id'    => (int) $slotId,
                        'day_of_week'       => $day,
                    ],
                    [
                        'is_open'          => (bool) ($values['is_open'] ?? false),
                        'min_notice_hours' => (int)  ($values['min_notice_hours'] ?? 48),
                        'cutoff_time'      => !empty($values['cutoff_time'])
                            ? $values['cutoff_time'] . ':00'
                            : null,
                    ]
                );
            }
        }

        session()->flash('status', 'Template settimanale salvato.');
        $this->loadSchedule();
    }

    public function render()
    {
        return view('livewire.vendor.offerings.manage-weekly-schedule');
    }
}