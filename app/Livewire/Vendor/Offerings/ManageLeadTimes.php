<?php

namespace App\Livewire\Vendor\Offerings;

use App\Models\VendorLeadTime;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ManageLeadTimes extends Component
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

    /**
     * Struttura: $leadTimes[day_of_week] = [
     *   'min_notice_hours' => int,
     *   'cutoff_time'      => string|null,
     * ]
     */
    public array $leadTimes = [];

    public function mount(): void
    {
        $this->authorize('viewAny', VendorLeadTime::class);
        $this->loadLeadTimes();
    }

    private function loadLeadTimes(): void
    {
        // Inizializza tutti i giorni con default 48h
        foreach (array_keys($this->days) as $day) {
            $this->leadTimes[$day] = [
                'min_notice_hours' => 48,
                'cutoff_time'      => '',
            ];
        }

        // Sovrascrive con valori salvati nel DB
        $saved = Auth::user()
            ->vendorAccount
            ->leadTimes()
            ->get();

        foreach ($saved as $row) {
            $this->leadTimes[$row->day_of_week] = [
                'min_notice_hours' => (int) $row->min_notice_hours,
                'cutoff_time'      => $row->cutoff_time
                    ? substr($row->cutoff_time, 0, 5)
                    : '',
            ];
        }
    }

    public function save(): void
    {
        $this->authorize('create', VendorLeadTime::class);

        $this->validate([
            'leadTimes.*.min_notice_hours' => 'required|integer|min:0|max:720',
            'leadTimes.*.cutoff_time'      => 'nullable|date_format:H:i',
        ]);

        $vendorAccount = Auth::user()->vendorAccount;

        foreach ($this->leadTimes as $day => $values) {
            $day = (int) $day;
            if ($day < 0 || $day > 6) continue;

            VendorLeadTime::updateOrCreate(
                [
                    'vendor_account_id' => $vendorAccount->id,
                    'day_of_week'       => $day,
                ],
                [
                    'min_notice_hours' => (int) ($values['min_notice_hours'] ?? 48),
                    'cutoff_time'      => !empty($values['cutoff_time'])
                        ? $values['cutoff_time'] . ':00'
                        : null,
                ]
            );
        }

        session()->flash('status', 'Preavvisi salvati.');
        $this->loadLeadTimes();
    }

    public function render()
    {
        return view('livewire.vendor.offerings.manage-lead-times');
    }
}