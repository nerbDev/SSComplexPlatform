<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ASSUMPTION: your existing `users.role` column is a plain string/varchar
     * (Laravel's default when you do $table->string('role')->default('client')),
     * not a MySQL ENUM. If it's a real ENUM, 'staff' needs to be added to the
     * enum list directly via a raw ALTER — same pattern as the
     * add_cancelled_finished_to_posters_status migration from the Posters
     * sprint. Check your original create_users_table migration to confirm
     * before running this.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 'client' | 'staff' | 'admin'
            // (no DB-level change needed if role is already a plain string column)

            $table->string('admin_type')->nullable()->after('role');
            // 'operation_building' | 'sports_activity' | null — only set when role = 'admin'
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_type');
        });
    }
};