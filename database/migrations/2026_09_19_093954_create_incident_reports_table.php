<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete(); // which booking caused it, if any
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete(); // staff

            $table->text('description');
            $table->text('damaged_items')->nullable();       // free text list; normalize into its own table later if it needs to be queryable
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->boolean('refund_related')->default(false); // flags it for OB Admin's refund workflow

            $table->string('photo_path')->nullable();
            $table->string('status')->default('open'); // open | under_review | resolved

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); // OB Admin
            $table->text('resolution_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};