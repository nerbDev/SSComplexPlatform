<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a planned maintenance job to the staff-filed maintenance report that triggered it, so
 * starting the job moves the report to in_progress and completing it resolves the report.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_schedules', function (Blueprint $table) {
            $table->foreignId('maintenance_report_id')->nullable()->after('equipment_inventory_id')
                ->constrained('maintenance_reports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_schedules', function (Blueprint $table) {
            $table->dropForeign(['maintenance_report_id']);
            $table->dropColumn('maintenance_report_id');
        });
    }
};