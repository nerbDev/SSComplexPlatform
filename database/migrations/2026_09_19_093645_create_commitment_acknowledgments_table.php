<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commitment_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('form_version')->default('v1'); // bump this whenever the commitment form's wording changes
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('acknowledged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commitment_acknowledgments');
    }
};