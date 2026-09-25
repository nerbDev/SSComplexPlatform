<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The client's Step 2 rate-mode choice (e.g. "first_3_hours", with or
     * without aircon) needs to be remembered between reservation submission
     * and the later Pay step, so PaymentController can recompute the exact
     * same amount without re-asking the client. Real columns instead of
     * parsing it back out of the free-text `notes` field.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('rate_type')->nullable()->after('booking_type');
            $table->boolean('aircon')->nullable()->after('rate_type');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['rate_type', 'aircon']);
        });
    }
};