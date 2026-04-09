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
        Schema::table('ie_layouts', function (Blueprint $table) {
            $table->float('total_smv')->default(0)->after('department');
            $table->float('man_power_sewer')->default(0)->after('total_smv');
            $table->float('man_power_matching')->default(0)->after('man_power_sewer');
            $table->float('man_power_qc')->default(0)->after('man_power_matching');
            $table->float('man_power_others')->default(0)->after('man_power_qc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ie_layouts', function (Blueprint $table) {
            $table->dropColumn(['total_smv', 'man_power_sewer', 'man_power_matching', 'man_power_qc', 'man_power_others']);
        });
    }
};
