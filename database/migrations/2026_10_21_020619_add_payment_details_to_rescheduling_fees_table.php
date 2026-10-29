<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original rescheduling_fees table only stored dates + the fee amount.
 * Client-side rescheduling also needs the time slots, the booking value the
 * 10% was taken from, and the same payment trail the main payment has
 * (GCash reference, generated Payment Order Slip, optional proof, timestamps).
 *
 * status values used: pending (awaiting GCash payment) -> submitted (client
 * paid, awaiting receipt confirmation) -> paid (confirmed, new slot applied),
 * plus waived (free-use, no fee) and cancelled (client abandoned an unpaid request).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rescheduling_fees', function (Blueprint $table) {
            $table->time('original_start_time')->nullable()->after('original_date');
            $table->time('original_end_time')->nullable()->after('original_start_time');
            $table->time('new_start_time')->nullable()->after('new_date');
            $table->time('new_end_time')->nullable()->after('new_start_time');

            $table->decimal('base_total', 10, 2)->nullable()->after('fee_percent'); // booking value the fee % was taken from

            $table->string('gcash_reference')->nullable()->after('status');
            $table->string('receipt_path')->nullable()->after('gcash_reference');
            $table->string('proof_of_payment_path')->nullable()->after('receipt_path');
            $table->text('client_notes')->nullable()->after('proof_of_payment_path');
            $table->timestamp('submitted_at')->nullable()->after('client_notes');
            $table->timestamp('confirmed_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('rescheduling_fees', function (Blueprint $table) {
            $table->dropColumn([
                'original_start_time', 'original_end_time', 'new_start_time', 'new_end_time',
                'base_total', 'gcash_reference', 'receipt_path', 'proof_of_payment_path',
                'client_notes', 'submitted_at', 'confirmed_at',
            ]);
        });
    }
};