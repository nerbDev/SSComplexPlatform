<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE posters MODIFY status ENUM('open','full','ongoing','cancelled','finished') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        // Revert cancelled/finished rows to 'open' before shrinking the enum, so the rollback doesn't fail on existing data.
        DB::table('posters')->whereIn('status', ['cancelled', 'finished'])->update(['status' => 'open']);

        DB::statement("ALTER TABLE posters MODIFY status ENUM('open','full','ongoing') NOT NULL DEFAULT 'open'");
    }
};