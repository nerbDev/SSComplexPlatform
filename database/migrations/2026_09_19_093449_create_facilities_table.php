<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // Function 1, Function 2, Function 3, Lobby and Whole Court
            $table->string('slug')->unique();
            $table->string('location')->nullable();      // e.g. "2nd Floor, Front"
            $table->unsignedInteger('capacity');          // hard pax limit
            $table->unsignedTinyInteger('safety_buffer_percent')->default(20); // configurable safety-capacity buffer
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};