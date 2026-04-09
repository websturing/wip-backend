<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->decimal('man_power_sewer', 8, 2)->default(0)->after('production_date');
            $table->decimal('man_power_matching', 8, 2)->default(0)->after('man_power_sewer');
            $table->decimal('man_power_qc', 8, 2)->default(0)->after('man_power_matching');
            $table->decimal('man_power_others', 8, 2)->default(0)->after('man_power_qc');
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn(['man_power_sewer', 'man_power_matching', 'man_power_qc', 'man_power_others']);
        });
    }
};
