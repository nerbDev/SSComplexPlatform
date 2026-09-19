<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete(); // null = walk-in, not tied to a booking

            $table->string('visitor_name');
            $table->string('organization')->nullable();
            $table->timestamp('time_in');
            $table->timestamp('time_out')->nullable();

            $table->foreignId('logged_by')->constrained('users')->cascadeOnDelete(); // staff at the desk

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};