<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\FacilityRate;
use Illuminate\Database\Seeder;

class FacilityRateSeeder extends Seeder
{
    /**
     * ASSUMPTION — flagged for confirmation, not guessed silently:
     * The Payment Order Slip lists rates under "GYM", "FUNCTION ROOM",
     * "LOBBY", and "COMMERCIAL SPACE" — four labels that don't map 1:1
     * onto our seeded facilities (Function 1/2/3, Lobby and Whole Court).
     * Mapping used here:
     *   - "GYM" rates      -> "Lobby and Whole Court" (the big multipurpose court)
     *   - "FUNCTION ROOM"  -> applied identically to Function 1, 2, and 3
     *   - "LOBBY" (as its own line) and "COMMERCIAL SPACE" are NOT seeded —
     *     they don't correspond to a facility in our current 4-facility set.
     * Confirm this mapping with SSC; it's easy to reseed once confirmed.
     */
    public function run(): void
    {
        $court = Facility::where('slug', 'lobby-and-whole-court')->first();

        if ($court) {
            FacilityRate::updateOrCreate(['facility_id' => $court->id, 'rate_type' => 'first_3_hours', 'aircon' => null], ['amount' => 15000]);
            FacilityRate::updateOrCreate(['facility_id' => $court->id, 'rate_type' => 'succeeding_hour', 'aircon' => null], ['amount' => 3500]);
            FacilityRate::updateOrCreate(['facility_id' => $court->id, 'rate_type' => 'whole_day', 'aircon' => null], ['amount' => 35000]);
            // "Per hour" mode is aircon-dependent for this facility specifically
            FacilityRate::updateOrCreate(['facility_id' => $court->id, 'rate_type' => 'per_hour', 'aircon' => true], ['amount' => 3000]);
            FacilityRate::updateOrCreate(['facility_id' => $court->id, 'rate_type' => 'per_hour', 'aircon' => false], ['amount' => 1000]);
        }

        foreach (['function-1', 'function-2', 'function-3'] as $slug) {
            $facility = Facility::where('slug', $slug)->first();
            if (!$facility) {
                continue;
            }

            FacilityRate::updateOrCreate(['facility_id' => $facility->id, 'rate_type' => 'first_3_hours', 'aircon' => null], ['amount' => 5000]);
            FacilityRate::updateOrCreate(['facility_id' => $facility->id, 'rate_type' => 'succeeding_hour', 'aircon' => null], ['amount' => 800]);
            FacilityRate::updateOrCreate(['facility_id' => $facility->id, 'rate_type' => 'whole_day', 'aircon' => null], ['amount' => 10000]);
        }
    }
}