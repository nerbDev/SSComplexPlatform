<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records who cancelled an appointment, when, and why, so the OB Admin's
 * cancellations/refunds workflow has something to report on. Both the client's
 * Cancel button and the OB Admin's "cancel on behalf" fill these in
 * (via AppointmentCancellationService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('verified_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancelled_by_role')->nullable()->after('cancelled_by'); // client | admin
            $table->text('cancellation_reason')->nullable()->after('cancelled_by_role');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancelled_by_role', 'cancellation_reason']);
        });
    }
};