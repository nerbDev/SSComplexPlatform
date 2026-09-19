<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();

            $table->decimal('base_amount', 10, 2);    // facility rate x hours, before tax
            $table->decimal('tax_amount', 10, 2)->default(0); // ingress tax — exact rule TBD w/ SSC Treasurer's Office
            $table->decimal('total_amount', 10, 2);

            $table->string('gcash_reference')->nullable();
            $table->string('status')->default('pending'); // pending | paid | failed
            $table->timestamp('paid_at')->nullable();
            $table->string('receipt_path')->nullable();   // generated printable receipt

            $table->foreignId('verified_by_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('forwarded_to_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('forwarded_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};