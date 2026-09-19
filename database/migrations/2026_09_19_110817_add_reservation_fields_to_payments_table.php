<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedTinyInteger('downpayment_percent')->default(40)->after('total_amount');
            $table->decimal('downpayment_amount', 10, 2)->default(0)->after('downpayment_percent');
            $table->decimal('balance_amount', 10, 2)->default(0)->after('downpayment_amount');

            // both default 0 for now per SSC — real ingress pricing isn't confirmed yet
            $table->decimal('ingress_before', 10, 2)->default(0)->after('balance_amount');
            $table->decimal('ingress_after', 10, 2)->default(0)->after('ingress_before');

            $table->unsignedTinyInteger('cancellation_refund_percent')->default(0)->after('ingress_after'); // 0% cashback if cancelled
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'downpayment_percent',
                'downpayment_amount',
                'balance_amount',
                'ingress_before',
                'ingress_after',
                'cancellation_refund_percent',
            ]);
        });
    }
};