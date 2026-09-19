<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->string('unit');            // Function 1 | Function 2 | Function 3 | Lobby and Whole Court
            $table->decimal('rate_per_hour', 10, 2);
            $table->string('note')->nullable(); // e.g. "Free for barangay-endorsed events"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};