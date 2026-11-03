<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PLANNED maintenance, scheduled by the Sports/Activity Admin (facility upkeep, equipment repair,
 * inspections). This is separate from the staff-filed maintenance_reports table: that one records
 * what staff found/did; this one records what the admin has planned and whether it got done.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('equipment_inventory_id')->nullable()->constrained('equipment_inventory')->nullOnDelete();

            $table->string('type')->default('routine'); // routine | inspection | repair | cleaning
            $table->string('title');
            $table->text('description')->nullable();

            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->string('status')->default('scheduled'); // scheduled | in_progress | completed | cancelled

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // staff
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();  // Sports/Activity Admin

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('outcome_condition')->nullable(); // condition the item was left in: good | damaged
            $table->text('completion_notes')->nullable();    // also holds the cancellation reason

            $table->timestamps();

            $table->index(['facility_id', 'scheduled_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};