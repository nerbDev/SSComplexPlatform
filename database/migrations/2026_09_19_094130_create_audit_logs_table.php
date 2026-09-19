<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // who performed the action

            $table->string('action');           // e.g. "appointment.status_changed", "poster.deleted"
            $table->string('subject_type')->nullable(); // e.g. App\Models\Appointment
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->text('description')->nullable();
            $table->ipAddress('ip_address')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};