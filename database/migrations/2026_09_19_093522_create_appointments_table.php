<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Formalizes the schema that the Admin/Client dashboard controllers from
     * earlier sprints guessed at (they queried a not-yet-real `appointments`
     * table with a loose `function_unit` string). This version normalizes
     * that into a real `facility_id` FK — those controllers will need a
     * small follow-up pass to join against `facilities` instead of matching
     * on a raw unit name string.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // the client who booked
            $table->foreignId('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignId('activity_type_id')->nullable()->constrained('activity_types')->nullOnDelete();

            $table->string('activity_title')->nullable(); // free-text description, e.g. "Alicos family wedding reception"
            $table->enum('track', ['paid', 'free_use']);

            // spans both tracks' pipelines — kept as a plain string rather than
            // an ENUM since the two tracks have different status vocabularies
            // (see Sprint 4's Municipal Approval Status Tracker for the full list)
            $table->string('status')->default('pending');

            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('expected_attendees')->nullable();

            $table->string('approval_form_path')->nullable(); // signed municipal approval upload, free-use track only
            $table->text('notes')->nullable();

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete(); // staff who verified
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['facility_id', 'event_date']); // conflict-free scheduling lookups
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};