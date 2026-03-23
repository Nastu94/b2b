<?php

namespace App\Services;

use App\Models\SlotLock;
use App\Models\VendorBlackout;
use App\Models\VendorLeadTime;
use App\Models\VendorOfferingProfile;
use App\Models\VendorSlot;
use App\Models\VendorWeeklySchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class AvailabilityService
{
    private const TIMEZONE = 'Europe/Rome';
    private array $contextCache = [];

    // Calcola la disponibilità su un range di date.
    public function getAvailability(
        int $vendorAccountId,
        string $from,
        string $to,
        int $maxDays = 90,
        ?int $offeringId = null,
        ?int $guests = null
    ): array {
        [$fromDate, $toDate] = $this->normalizeRange($from, $to, $maxDays);
        $now = CarbonImmutable::now(self::TIMEZONE);

        $context = $this->buildContext(
            vendorAccountId: $vendorAccountId,
            fromDate: $fromDate,
            toDate: $toDate,
            offeringId: $offeringId,
            now: $now
        );

        $out = [];

        for ($date = $fromDate; $date->lessThanOrEqualTo($toDate); $date = $date->addDay()) {
            $dayKey = $date->toDateString();
            $dayOfWeek = (int) $date->dayOfWeek;

            $out[$dayKey] = [];

            foreach ($context['slots'] as $slot) {
                $result = $this->evaluateSlotAvailability(
                    slot: $slot,
                    date: $date,
                    dayOfWeek: $dayOfWeek,
                    now: $now,
                    weekly: $context['weekly'],
                    leadTimes: $context['leadTimes'],
                    blackouts: $context['blackouts'],
                    lockedIndex: $context['lockedIndex'],
                    profile: $context['profile'],
                    guests: $guests
                );

                $out[$dayKey][] = $this->row(
                    slot: $slot,
                    status: $result['status'],
                    reason: $result['reason']
                );
            }
        }

        return $out;
    }

    // Verifica puntuale usata da hold e validazioni.
    public function assertSlotBookable(
        int $vendorAccountId,
        int $vendorSlotId,
        string $date,
        ?int $offeringId = null,
        ?int $guests = null
    ): void {
        $targetDate = CarbonImmutable::createFromFormat('Y-m-d', $date, self::TIMEZONE)->startOfDay();
        $now = CarbonImmutable::now(self::TIMEZONE);

        $context = $this->buildContext(
            vendorAccountId: $vendorAccountId,
            fromDate: $targetDate,
            toDate: $targetDate,
            offeringId: $offeringId,
            now: $now
        );

        /** @var VendorSlot|null $slot */
        $slot = $context['slots']->firstWhere('id', $vendorSlotId);

        if (! $slot) {
            throw new RuntimeException('Slot non valido o non attivo');
        }

        $result = $this->evaluateSlotAvailability(
            slot: $slot,
            date: $targetDate,
            dayOfWeek: (int) $targetDate->dayOfWeek,
            now: $now,
            weekly: $context['weekly'],
            leadTimes: $context['leadTimes'],
            blackouts: $context['blackouts'],
            lockedIndex: $context['lockedIndex'],
            profile: $context['profile'],
            guests: $guests
        );

        if ($result['status'] !== 'AVAILABLE') {
            throw new RuntimeException($this->reasonToMessage($result['reason']));
        }
    }

    private function buildContext(
        int $vendorAccountId,
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate,
        ?int $offeringId,
        CarbonImmutable $now
    ): array {
        // Utilizza una cache in memoria per archiviare il contesto (slot, blackout, ecc.) e
        // prevenire frammentazioni di query al database per lo stesso fornitore nella stessa richiesta.
        $cacheKey = "{$vendorAccountId}_{$fromDate->toDateString()}_{$toDate->toDateString()}";

        if (!isset($this->contextCache[$cacheKey])) {
            /** @var Collection<int, VendorSlot> $slots */
            $slots = VendorSlot::query()
                ->where('vendor_account_id', $vendorAccountId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(['id', 'slug', 'label', 'start_time', 'end_time']);

            $weekly = VendorWeeklySchedule::query()
                ->where('vendor_account_id', $vendorAccountId)
                ->get(['vendor_slot_id', 'day_of_week', 'is_open'])
                ->keyBy(fn ($row) => $row->vendor_slot_id . ':' . $row->day_of_week);

            $leadTimes = VendorLeadTime::query()
                ->where('vendor_account_id', $vendorAccountId)
                ->get(['day_of_week', 'min_notice_hours', 'cutoff_time'])
                ->keyBy('day_of_week');

            $blackouts = VendorBlackout::query()
                ->where('vendor_account_id', $vendorAccountId)
                ->whereDate('date_from', '<=', $toDate->toDateString())
                ->whereDate('date_to', '>=', $fromDate->toDateString())
                ->get(['date_from', 'date_to', 'vendor_slot_id'])
                ->all();

            $lockedIndex = $this->buildLockedIndex(
                vendorAccountId: $vendorAccountId,
                fromDate: $fromDate,
                toDate: $toDate,
                now: $now
            );

            $this->contextCache[$cacheKey] = [
                'slots' => $slots,
                'weekly' => $weekly,
                'leadTimes' => $leadTimes,
                'blackouts' => $blackouts,
                'lockedIndex' => $lockedIndex,
            ];
        }

        $profile = $this->loadOfferingProfile($vendorAccountId, $offeringId);

        return array_merge($this->contextCache[$cacheKey], ['profile' => $profile]);
    }

    private function normalizeRange(string $from, string $to, int $maxDays): array
    {
        $fromDate = CarbonImmutable::createFromFormat('Y-m-d', $from, self::TIMEZONE)->startOfDay();
        $toDate = CarbonImmutable::createFromFormat('Y-m-d', $to, self::TIMEZONE)->startOfDay();

        if ($toDate->lessThan($fromDate)) {
            throw new InvalidArgumentException('Invalid date range: to < from');
        }

        $daysCount = $fromDate->diffInDays($toDate) + 1;

        if ($daysCount > $maxDays) {
            throw new InvalidArgumentException("Date range too large (max {$maxDays} days)");
        }

        return [$fromDate, $toDate];
    }

    private function loadOfferingProfile(int $vendorAccountId, ?int $offeringId): ?VendorOfferingProfile
    {
        if ($offeringId === null) {
            return null;
        }

        return VendorOfferingProfile::query()
            ->where('vendor_account_id', $vendorAccountId)
            ->where('offering_id', $offeringId)
            ->first();
    }

    private function evaluateSlotAvailability(
        VendorSlot $slot,
        CarbonImmutable $date,
        int $dayOfWeek,
        CarbonImmutable $now,
        Collection $weekly,
        Collection $leadTimes,
        array $blackouts,
        array $lockedIndex,
        ?VendorOfferingProfile $profile,
        ?int $guests
    ): array {
        $lead = $leadTimes->get($dayOfWeek);
        $minNoticeHours = (int) ($lead->min_notice_hours ?? 48);
        $cutoffTime = $lead && $lead->cutoff_time
            ? substr((string) $lead->cutoff_time, 0, 8)
            : null;

        $startTime = $slot->start_time
            ? substr((string) $slot->start_time, 0, 8)
            : '00:00:00';

        $eventMoment = $date->setTimeFromTimeString($startTime);

        if (! $this->passesLeadTime($now, $eventMoment, $minNoticeHours, $cutoffTime)) {
            return ['status' => 'BLOCKED', 'reason' => 'LEAD_TIME'];
        }

        if (! $this->isOpenBySchedule($weekly, (int) $slot->id, $dayOfWeek)) {
            return ['status' => 'BLOCKED', 'reason' => 'SCHEDULE'];
        }

        if ($this->isBlackouted($blackouts, $date, (int) $slot->id)) {
            return ['status' => 'BLOCKED', 'reason' => 'BLACKOUT'];
        }

        if ($this->isSlotLockedFromIndex($lockedIndex, $date->toDateString(), (int) $slot->id)) {
            return ['status' => 'BLOCKED', 'reason' => 'BOOKED'];
        }

        if ($this->profileExceedsCapacity($profile, $guests)) {
            return ['status' => 'BLOCKED', 'reason' => 'CAPACITY'];
        }

        return ['status' => 'AVAILABLE', 'reason' => null];
    }

    private function buildLockedIndex(
        int $vendorAccountId,
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate,
        CarbonImmutable $now
    ): array {
        $locks = SlotLock::query()
            ->where('vendor_account_id', $vendorAccountId)
            ->whereDate('date', '>=', $fromDate->toDateString())
            ->whereDate('date', '<=', $toDate->toDateString())
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->where('status', SlotLock::STATUS_BOOKED)
                    ->orWhere(function ($subQuery) use ($now) {
                        $subQuery->where('status', SlotLock::STATUS_HOLD)
                            ->where('expires_at', '>', $now);
                    });
            })
            ->get(['vendor_slot_id', 'date']);

        $index = [];

        foreach ($locks as $lock) {
            $day = substr((string) $lock->date, 0, 10);
            $slotId = (int) $lock->vendor_slot_id;
            $index[$day][$slotId] = true;
        }

        return $index;
    }

    private function isSlotLockedFromIndex(array $lockedIndex, string $date, int $vendorSlotId): bool
    {
        return isset($lockedIndex[$date][$vendorSlotId]);
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

    private function isOpenBySchedule(Collection $weeklyKeyed, int $vendorSlotId, int $dayOfWeek): bool
    {
        $record = $weeklyKeyed->get($vendorSlotId . ':' . $dayOfWeek);

        return (bool) ($record?->is_open ?? false);
    }

    private function isBlackouted(array $blackouts, CarbonImmutable $date, int $vendorSlotId): bool
    {
        $day = $date->toDateString();

        foreach ($blackouts as $blackout) {
            if ($blackout->date_from <= $day && $blackout->date_to >= $day) {
                if ($blackout->vendor_slot_id === null || (int) $blackout->vendor_slot_id === $vendorSlotId) {
                    return true;
                }
            }
        }

        return false;
    }

    private function passesLeadTime(
        CarbonImmutable $now,
        CarbonImmutable $eventMoment,
        int $minNoticeHours,
        ?string $cutoffTime
    ): bool {
        if ($now->diffInMinutes($eventMoment, false) < ($minNoticeHours * 60)) {
            return false;
        }

        if ($cutoffTime) {
            $eventDate = $eventMoment->startOfDay();
            $today = $now->startOfDay();

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

    private function profileExceedsCapacity(?VendorOfferingProfile $profile, ?int $guests): bool
    {
        if (! $profile || $guests === null) {
            return false;
        }

        if (method_exists($profile, 'isFixedLocationService') && ! $profile->isFixedLocationService()) {
            return false;
        }

        if (
            ! method_exists($profile, 'isFixedLocationService')
            && isset($profile->service_mode)
            && $profile->service_mode !== 'FIXED_LOCATION'
        ) {
            return false;
        }

        if (method_exists($profile, 'exceedsCapacity')) {
            return $profile->exceedsCapacity($guests);
        }

        if (! isset($profile->max_guests) || $profile->max_guests === null) {
            return false;
        }

        return $guests > (int) $profile->max_guests;
    }

    private function reasonToMessage(?string $reason): string
    {
        return match ($reason) {
            'LEAD_TIME' => 'Slot non prenotabile per vincolo di anticipo minimo',
            'SCHEDULE' => 'Slot non disponibile nel calendario vendor',
            'BLACKOUT' => 'Slot non disponibile per blackout vendor',
            'BOOKED' => 'Slot non disponibile',
            'CAPACITY' => 'Numero ospiti non supportato per questo servizio',
            default => 'Slot non disponibile',
        };
    }
}