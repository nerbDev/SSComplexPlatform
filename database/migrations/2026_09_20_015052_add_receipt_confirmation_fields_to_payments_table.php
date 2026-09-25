<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // system-generated Payment Order Slip is already covered by
            // the existing `receipt_path` column — these are for the
            // client's own Step 4 "Receipt Confirmation" action
            $table->string('proof_of_payment_path')->nullable()->after('receipt_path');
            $table->text('client_notes')->nullable()->after('proof_of_payment_path');
            $table->timestamp('receipt_confirmed_at')->nullable()->after('client_notes');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['proof_of_payment_path', 'client_notes', 'receipt_confirmed_at']);
        });
    }
};