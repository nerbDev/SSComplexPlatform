<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sprint 1's rescheduling_fees table defaulted fee_percent to 20 as a
     * placeholder. Sprint 2 confirms the real figure: 10% additional for
     * rescheduling a reservation. This only changes the column's DB-level
     * default — always pass fee_percent explicitly when creating a
     * ReschedulingFee row rather than relying on the default.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE rescheduling_fees MODIFY fee_percent TINYINT UNSIGNED NOT NULL DEFAULT 10");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE rescheduling_fees MODIFY fee_percent TINYINT UNSIGNED NOT NULL DEFAULT 20");
    }
};