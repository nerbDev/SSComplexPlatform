<?php

namespace App\Services;

use App\Models\Facility;

class FacilityPricingService
{
    /**
     * Server-side amount computation — shared by ReservationController
     * (Step 2 preview/validation) and PaymentController (actual charge at
     * payment time), so the two can never drift out of sync.
     *
     * Mirrors the Payment Order Slip's modes of rent:
     *   - whole_day: flat rate regardless of duration
     *   - per_hour: hourly rate x duration (aircon-dependent where applicable)
     *   - first_3_hours: flat rate + (hours beyond 3) x succeeding_hour rate
     *   - succeeding_hour: hourly rate x duration
     */
    public function computeAmount(Facility $facility, string $rateType, ?bool $aircon, string $startTime, string $endTime): ?float
    {
        $schedule = $facility->rate_schedule;
        $hours = (strtotime($endTime) - strtotime($startTime)) / 3600;

        $rateFor = function (string $type) use ($schedule, $aircon) {
            if (!isset($schedule[$type])) {
                return null;
            }
            if (isset($schedule[$type]['default'])) {
                return $schedule[$type]['default'];
            }
            return $aircon ? ($schedule[$type]['aircon'] ?? null) : ($schedule[$type]['no_aircon'] ?? null);
        };

        return match ($rateType) {
            'whole_day' => $rateFor('whole_day'),
            'per_hour'  => ($r = $rateFor('per_hour')) !== null ? round($r * $hours, 2) : null,
            'succeeding_hour' => ($r = $rateFor('succeeding_hour')) !== null ? round($r * $hours, 2) : null,
            'first_3_hours' => (function () use ($rateFor, $hours) {
                $base = $rateFor('first_3_hours');
                if ($base === null) {
                    return null;
                }
                $extraHours = max(0, $hours - 3);
                $succeeding = $rateFor('succeeding_hour') ?? 0;
                return round($base + $extraHours * $succeeding, 2);
            })(),
            default => null,
        };
    }
}




























