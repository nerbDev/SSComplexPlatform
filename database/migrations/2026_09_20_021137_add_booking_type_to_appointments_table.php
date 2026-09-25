<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinguishes the two paid-track booking types the client chooses
     * between in Step 1 of the reservation wizard:
     *   - 'appointment'      -> full payment upfront, 10% rescheduling fee, ingress applies
     *   - 'room_reservation' -> 40% downpayment now, 60% balance due 3 days
     *                           before the event (for long-lead-time bookings)
     * Both still use `track = 'paid'` — this is a distinction within the
     * paid track, separate from paid vs free_use.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('booking_type')->default('appointment')->after('track');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('booking_type');
        });
    }
};