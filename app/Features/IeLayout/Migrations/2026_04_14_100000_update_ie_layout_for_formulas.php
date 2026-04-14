<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ie_layouts', function (Blueprint $table) {
            $table->decimal('efficiency_constant', 8, 2)->default(1.00)->after('price');
        });

        Schema::table('time_studies', function (Blueprint $table) {
            $table->string('section')->default('INLINE')->after('ie_layout_id');
            $table->decimal('std_time', 10, 3)->nullable()->after('machine_turn');
            $table->decimal('target_hour', 10, 3)->nullable()->after('std_time');
            $table->decimal('target_day', 10, 3)->nullable()->after('target_hour');
            $table->decimal('smv', 10, 3)->nullable()->after('target_day');
            
            // Modify existing columns to decimal for better precision
            $table->decimal('handling_position_value', 10, 3)->nullable()->after('handling_position');
            $table->decimal('length', 10, 3)->change();
            $table->decimal('machine_turn', 10, 3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ie_layouts', function (Blueprint $table) {
            $table->dropColumn('efficiency_constant');
        });

        Schema::table('time_studies', function (Blueprint $table) {
            $table->dropColumn(['section', 'std_time', 'target_hour', 'target_day', 'smv', 'handling_position_value']);
        });
    }
};
