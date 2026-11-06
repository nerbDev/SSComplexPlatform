<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visitor logging records the event organizer/leader and how many people arrived with them,
 * which is what Sprint 12's peak-hours analytics needs (a head count over time, not just names).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->unsignedInteger('party_size')->default(1)->after('organization');
            $table->boolean('is_organizer')->default(false)->after('party_size'); // the event organizer / group leader
        });
    }

    public function down(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dropColumn(['party_size', 'is_organizer']);
        });
    }
};