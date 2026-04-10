<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productivities', function (Blueprint $table) {
            $table->decimal('sewer', 8, 2)->default(0)->after('date');
            $table->decimal('plan_sewer', 8, 2)->default(0)->after('sewer');
            
            // Ensure existing manpower fields are decimal (already are, but to be safe)
            $table->decimal('manpower', 8, 2)->default(0)->change();
            $table->decimal('plan_manpower', 8, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productivities', function (Blueprint $table) {
            $table->dropColumn(['sewer', 'plan_sewer']);
        });
    }
};
