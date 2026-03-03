<?php

namespace App\Services;

use App\Models\VendorBlackout;
use App\Models\VendorLeadTime;
use App\Models\VendorSlot;
use App\Models\VendorWeeklySchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use App\Models\SlotLock;

class AvailabilityService
{
    private string $tz = 'Europe/Rome';

    /**
     * Disponibilità per vendor in un range di date, per tutti gli slot attivi.
     *
     * Output:
     * [
     *   'YYYY-MM-DD' => [
     *     [
     *       'vendor_slot_id' => 1,
     *       'slug' => 'evening',
     *       'label' => 'Sera',
     *       'start_time' => '20:00:00',
     *       'end_time' => '23:00:00',
     *       'status' => 'AVAILABLE' | 'BLOCKED',
     *       'reason' => null | 'LEAD_TIME' | 'SCHEDULE' | 'BLACKOUT' | 'BOOKED',
     *     ],
     *     ...
     *   ],
     * ]
     */
    public function getAvailability(int $vendorAccountId, string $from, string $to, int $maxDays = 90): array
    {
        $fromDate = CarbonImmutable::createFromFormat('Y-m-d', $from, $this->tz)->startOfDay();
        $toDate   = CarbonImmutable::createFromFormat('Y-m-d', $to, $this->tz)->startOfDay();

        if ($toDate->lessThan($fromDate)) {
            throw new \InvalidArgumentException('Invalid date range: to < from');
        }

        $daysCount = $fromDate->diffInDays($toDate) + 1;
        if ($daysCount > $maxDays) {
            throw new \InvalidArgumentException("Date range too large (max {$maxDays} days)");
        }

        $now = CarbonImmutable::now($this->tz);

        /** @var Collection<int, VendorSlot> $slots */
        $slots = VendorSlot::query()
            ->where('vendor_account_id', $vendorAccountId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['id', 'slug', 'label', 'start_time', 'end_time']);

        // Weekly schedule: chiave "vendor_slot_id:day_of_week"
        // Default: CHIUSO se record mancante (come tua logica)
        $weekly = VendorWeeklySchedule::query()
            ->where('vendor_account_id', $vendorAccountId)
            ->get(['vendor_slot_id', 'day_of_week', 'is_open'])
            ->keyBy(fn($r) => $r->vendor_slot_id . ':' . $r->day_of_week);

        // Lead time per giorno: fallback 48h se non esiste record
        $leadTimes = VendorLeadTime::query()
            ->where('vendor_account_id', $vendorAccountId)
            ->get(['day_of_week', 'min_notice_hours', 'cutoff_time'])
            ->keyBy('day_of_week');

        // Blackouts nel range
        $blackouts = VendorBlackout::query()
            ->where('vendor_account_id', $vendorAccountId)
            ->whereDate('date_from', '<=', $toDate->toDateString())
            ->whereDate('date_to', '>=', $fromDate->toDateString())
            ->get(['date_from', 'date_to', 'vendor_slot_id'])
            ->all();

        $out = [];

        for ($d = $fromDate; $d->lessThanOrEqualTo($toDate); $d = $d->addDay()) {
            $dayKey = $d->toDateString();
            $dow = (int) $d->dayOfWeek; // 0=Sunday..6=Saturday (coerente con le tue migration)

            $lead = $leadTimes->get($dow);
            $minNoticeHours = (int) ($lead->min_notice_hours ?? 48);
            $cutoffTime = $lead->cutoff_time ? substr((string) $lead->cutoff_time, 0, 8) : null;

            $out[$dayKey] = [];

            foreach ($slots as $slot) {
                // Momento reale di inizio evento = data + start_time (o 00:00 se null)
                $startTime = $slot->start_time ? substr((string) $slot->start_time, 0, 8) : '00:00:00';
                $eventMoment = $d->setTimeFromTimeString($startTime);

                // 1) LEAD_TIME (PDF: fuori lead time => BLOCKED(LEAD_TIME))
                if (!$this->passesLeadTimePdf($now, $eventMoment, $minNoticeHours, $cutoffTime)) {
                    $out[$dayKey][] = $this->row($slot, 'BLOCKED', 'LEAD_TIME');
                    continue;
                }

                // 2) SCHEDULE (PDF: se slot non previsto dal template => BLOCKED(SCHEDULE))
                if (!$this->isOpenBySchedule($weekly, (int) $slot->id, $dow)) {
                    $out[$dayKey][] = $this->row($slot, 'BLOCKED', 'SCHEDULE');
                    continue;
                }

                // 3) BLACKOUT (PDF: se blackout => BLOCKED(BLACKOUT))
                if ($this->isBlackouted($blackouts, $d, (int) $slot->id)) {
                    $out[$dayKey][] = $this->row($slot, 'BLOCKED', 'BLACKOUT');
                    continue;
                }

                // 4) BOOKED / HOLD (PDF)
                if ($this->isSlotLocked(
                    $vendorAccountId,
                    (int) $slot->id,
                    $dayKey,
                    $now
                )) {
                    $out[$dayKey][] = $this->row($slot, 'BLOCKED', 'BOOKED');
                    continue;
                }

                $out[$dayKey][] = $this->row($slot, 'AVAILABLE', null);
            }
        }

        return $out;
    }

    private function row(VendorSlot $slot, string $status, ?string $reason): array
    {
        return [
            'vendor_slot_id' => (int) $slot->id,
            'slug' => (string) $slot->slug,
            'label' => (string) $slot->label,
            'start_time' => $slot->start_time ? substr((string) $slot->start_time, 0, 8) : null,
            'end_time' => $slot->end_time ? substr((string) $slot->end_time, 0, 8) : null,
            'status' => $status,
            'reason' => $reason,
        ];
    }

    private function isOpenBySchedule(Collection $weeklyKeyed, int $vendorSlotId, int $dow): bool
    {
        $rec = $weeklyKeyed->get($vendorSlotId . ':' . $dow);

        // default closed
        return (bool) ($rec?->is_open ?? false);
    }

    private function isBlackouted(array $blackouts, CarbonImmutable $date, int $vendorSlotId): bool
    {
        $day = $date->toDateString();

        foreach ($blackouts as $b) {
            // range date match
            if ($b->date_from <= $day && $b->date_to >= $day) {
                // vendor_slot_id null => tutti gli slot
                if ($b->vendor_slot_id === null || (int) $b->vendor_slot_id === $vendorSlotId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Implementazione LEAD_TIME coerente con PDF:
     * - min_notice_hours: ora deve essere almeno N ore prima del MOMENTO evento (data+start_time)
     * - cutoff_time (opzionale): prenotazioni per il giorno D entro cutoff del giorno D-1
     *   (esempio PDF: prenotazioni per domani entro le 18:00 di oggi)
     */
    private function passesLeadTimePdf(
        CarbonImmutable $now,
        CarbonImmutable $eventMoment,
        int $minNoticeHours,
        ?string $cutoffTime
    ): bool {
        // 1) min_notice_hours
        if ($now->diffInMinutes($eventMoment, false) < ($minNoticeHours * 60)) {
            return false;
        }

        // 2) cutoff_time applicato al giorno precedente all'evento (se evento da domani in poi)
        if ($cutoffTime) {
            $eventDate = $eventMoment->startOfDay();
            $today = $now->startOfDay();

            // solo eventi futuri (>= domani). Se eventDate == today, cutoff non ha molto senso.
            if ($eventDate->greaterThan($today)) {
                $cutoffDay = $eventDate->subDay();
                $cutoffMoment = $cutoffDay->setTimeFromTimeString($cutoffTime);

                if ($now->greaterThan($cutoffMoment)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Stub: fino alla Fase 2 (locks), non blocchiamo mai per BOOKED.
     * Quando introduciamo slot_locks + unique constraint, qui diventa un check reale.
     */
    private function isBookedStubFalse(): bool
    {
        return false;
    }

    private function isSlotLocked(
    int $vendorAccountId,
    int $vendorSlotId,
    string $date,
    CarbonImmutable $now
): bool {

    return SlotLock::query()
        ->where('vendor_account_id', $vendorAccountId)
        ->where('vendor_slot_id', $vendorSlotId)
        ->whereDate('date', $date)
        ->where('is_active', true)
        ->where(function ($q) use ($now) {
            $q->where('status', 'BOOKED')
              ->orWhere(function ($sub) use ($now) {
                  $sub->where('status', 'HOLD')
                      ->where('expires_at', '>', $now);
              });
        })
        ->exists();
}
}
