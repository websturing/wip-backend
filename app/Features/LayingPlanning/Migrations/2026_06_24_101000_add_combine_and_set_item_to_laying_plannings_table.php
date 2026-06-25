<?php

namespace App\Features\LayingPlanning\Migrations;

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
        Schema::table('laying_plannings', function (Blueprint $table) {
            $table->uuid('laying_planning_combine_id')->nullable()->after('is_combine');
            $table->boolean('is_set_item')->default(false)->after('laying_planning_combine_id');

            $table->foreign('laying_planning_combine_id')
                ->references('id')
                ->on('laying_planning_combines')
                ->onDelete('set null');

            $table->index('laying_planning_combine_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laying_plannings', function (Blueprint $table) {
            $table->dropForeign(['laying_planning_combine_id']);
            $table->dropIndex(['laying_planning_combine_id']);
            $table->dropColumn(['laying_planning_combine_id', 'is_set_item']);
        });
    }
};
