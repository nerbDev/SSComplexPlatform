<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refund workflow (OB Admin): pending -> approved -> released, or pending -> rejected.
 * Payments are collected on-site at the Municipality, so "released" only records that
 * the refund was handed back, with the on-site reference (voucher / OR number).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('incident_report_id')->nullable()->constrained('incident_reports')->nullOnDelete();

            $table->string('reason_type'); // ssc_cancellation | force_majeure | incident | overpayment | other
            $table->text('reason')->nullable();

            $table->decimal('payment_total', 10, 2);       // the payment total the % was taken from
            $table->unsignedTinyInteger('refund_percent');
            $table->decimal('refund_amount', 10, 2);

            $table->string('status')->default('pending'); // pending | approved | released | rejected

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->string('release_reference')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();

            $table->timestamps();

            $table->index(['appointment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};