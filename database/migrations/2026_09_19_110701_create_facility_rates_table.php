<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the flat `rates` table (one rate_per_hour per facility) with
     * a real rate schedule matching the municipal Payment Order Slip:
     * each facility can have several "modes of rent" (1st 3 hours,
     * succeeding hour, whole day, per hour), and some of those modes vary
     * by aircon (on/off). `aircon` is nullable — null means "not applicable
     * to this facility/rate combo", not "without aircon".
     *
     * The existing `rates` table (used by the client dashboard's
     * "Rates & Activity Types" card) is left untouched for now — swap that
     * card to read from this table in a follow-up pass.
     */
    public function up(): void
    {
        Schema::create('facility_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();

            $table->string('rate_type'); // first_3_hours | succeeding_hour | whole_day | per_hour
            $table->boolean('aircon')->nullable(); // null = n/a for this facility/rate_type

            $table->decimal('amount', 10, 2);

            $table->timestamps();

            $table->unique(['facility_id', 'rate_type', 'aircon'], 'facility_rate_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_rates');
    }
};