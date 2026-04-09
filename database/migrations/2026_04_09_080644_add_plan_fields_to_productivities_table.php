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
            $table->decimal('plan_manpower', 8, 2)->default(0)->after('manpower');
            $table->decimal('target_plan', 10, 2)->default(0)->after('working_hour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productivities', function (Blueprint $table) {
            $table->dropColumn(['plan_manpower', 'target_plan']);
        });
    }
};
