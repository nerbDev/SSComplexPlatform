<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unavailable_slots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('start_time')->nullable(); // null + null start/end = whole day blocked
            $table->time('end_time')->nullable();
            $table->string('unit')->nullable();      // null = applies to all units
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unavailable_slots');
    }
};