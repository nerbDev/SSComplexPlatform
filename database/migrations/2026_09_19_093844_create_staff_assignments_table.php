<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete(); // the admin who assigned them

            // mirrors the event's own start/end time by default — flexible
            // per-event scheduling, not a fixed shift
            $table->time('start_time');
            $table->time('end_time');

            $table->string('status')->default('assigned'); // assigned | confirmed | completed
            $table->timestamps();

            $table->unique(['appointment_id', 'staff_id']); // no duplicate assignment of the same staff to the same event
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_assignments');
    }
};