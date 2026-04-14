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
        Schema::table('productivity_lots', function (Blueprint $table) {
            $table->decimal('manpower', 8, 2)->default(0);
            $table->decimal('plan_manpower', 8, 2)->default(0);
            $table->decimal('sewer', 8, 2)->default(0);
            $table->decimal('plan_sewer', 8, 2)->default(0);
            $table->decimal('working_hour', 5, 2)->default(8);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productivity_lots', function (Blueprint $table) {
            $table->dropColumn(['manpower', 'plan_manpower', 'sewer', 'plan_sewer', 'working_hour']);
        });
    }
};
