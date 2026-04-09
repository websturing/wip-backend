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
            $table->decimal('smv', 8, 3)->default(0);
            $table->decimal('last_step', 10, 2)->default(0);
            $table->decimal('target_plan', 10, 2)->default(0);
        });

        Schema::table('productivities', function (Blueprint $table) {
            $table->decimal('smv', 8, 3)->nullable()->change();
            $table->decimal('last_step', 10, 2)->nullable()->change();
            $table->decimal('target_plan', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productivity_lots', function (Blueprint $table) {
            $table->dropColumn(['smv', 'last_step', 'target_plan']);
        });

        Schema::table('productivities', function (Blueprint $table) {
            $table->decimal('smv', 8, 3)->nullable(false)->change();
            $table->decimal('last_step', 10, 2)->nullable(false)->change();
            $table->decimal('target_plan', 10, 2)->nullable(false)->change();
        });
    }
};
