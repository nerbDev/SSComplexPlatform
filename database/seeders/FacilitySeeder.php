<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            [
                'name'     => 'Function 1',
                'slug'     => 'function-1',
                'location' => '2nd Floor, Front',
                'capacity' => 300,
            ],
            [
                'name'     => 'Function 2',
                'slug'     => 'function-2',
                'location' => '2nd Floor, Rear',
                'capacity' => 200,
            ],
            [
                'name'     => 'Function 3',
                'slug'     => 'function-3',
                'location' => '1st Floor, near the Whole Court',
                'capacity' => 200,
            ],
            [
                'name'     => 'Lobby and Whole Court',
                'slug'     => 'lobby-and-whole-court',
                'location' => 'Ground Floor',
                'capacity' => 2000,
            ],
        ];

        foreach ($facilities as $facility) {
            Facility::updateOrCreate(
                ['slug' => $facility['slug']],
                $facility + ['safety_buffer_percent' => 20, 'is_active' => true]
            );
        }
    }
}