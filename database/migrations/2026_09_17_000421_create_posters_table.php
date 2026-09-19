<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posters', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_path');
            $table->date('event_date')->nullable();
            $table->string('event_time')->nullable(); // free text, e.g. "6:00 PM – 8:00 PM"
            $table->string('unit')->nullable();        // Function 1 | Function 2 | Function 3 | Lobby and Whole Court
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'full', 'ongoing'])->default('open');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posters');
    }
};