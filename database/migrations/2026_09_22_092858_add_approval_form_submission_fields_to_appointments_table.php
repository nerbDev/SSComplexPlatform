<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * appointments.approval_form_path already existed since Sprint 1 (it
     * was originally meant for the municipal approval upload on the
     * free_use track). These two columns capture the rest of what the
     * client's "Attach Form" modal collects: who submitted it and when —
     * distinct from Laravel's own created_at/updated_at, since the actual
     * physical form may have been submitted to the Subic Administration
     * Office at a different time than when the client uploads it here.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('approval_form_submitted_at')->nullable()->after('approval_form_path');
            $table->string('approval_form_submitted_by')->nullable()->after('approval_form_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['approval_form_submitted_at', 'approval_form_submitted_by']);
        });
    }
};