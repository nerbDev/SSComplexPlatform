<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete(); // staff

            $table->string('title');
            $table->text('description');
            $table->string('severity')->default('low'); // low | medium | high
            $table->string('status')->default('open');  // open | in_progress | resolved
            $table->string('photo_path')->nullable();

            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete(); // sports/activity admin
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_reports');
    }
};