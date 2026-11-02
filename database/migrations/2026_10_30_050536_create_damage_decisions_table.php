<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The OB Admin's decisions on a damage/incident report (authority-to-act side).
 * One incident can carry several decisions (e.g. charge for the chairs, waive the curtain).
 * The incident itself is captured by staff in Sprint 10; this table only records what the admin decided.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_report_id')->constrained('incident_reports')->cascadeOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete(); // OB Admin

            $table->string('decision'); // charge_client | waive | repair | replace | no_damage

            $table->foreignId('equipment_inventory_id')->nullable()->constrained('equipment_inventory')->nullOnDelete();
            $table->string('item_label')->nullable();           // free-text item when it isn't in the inventory
            $table->unsignedInteger('quantity_affected')->nullable();
            $table->decimal('approved_amount', 10, 2)->nullable(); // amount charged / approved for repair or replacement
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('incident_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_decisions');
    }
};