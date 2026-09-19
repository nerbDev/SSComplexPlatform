<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rescheduling_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();

            $table->date('original_date');
            $table->date('new_date');

            $table->unsignedTinyInteger('fee_percent')->default(20); // confirm exact % with SSC
            $table->decimal('fee_amount', 10, 2);
            $table->string('status')->default('pending'); // pending | paid | waived

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rescheduling_fees');
    }
};