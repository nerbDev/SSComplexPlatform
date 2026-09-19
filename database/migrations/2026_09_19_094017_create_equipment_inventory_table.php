<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete(); // null = shared/general stock

            $table->string('item_name');
            $table->unsignedInteger('quantity')->default(0);
            $table->string('unit')->nullable(); // pcs, sets, etc.
            $table->string('condition')->default('good'); // good | damaged | under_maintenance

            $table->foreignId('managed_by')->nullable()->constrained('users')->nullOnDelete(); // sports/activity admin
            $table->timestamp('last_checked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_inventory');
    }
};