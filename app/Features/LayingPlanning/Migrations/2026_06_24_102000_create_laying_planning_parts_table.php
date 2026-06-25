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
        if (!Schema::hasTable('laying_planning_parts')) {
            Schema::create('laying_planning_parts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('laying_planning_id');
                $table->string('item_part');
                $table->string('item_part_group_code')->nullable();
                $table->timestamps();

                $table->foreign('laying_planning_id')
                    ->references('id')
                    ->on('laying_plannings')
                    ->onDelete('cascade');

                $table->index('laying_planning_id');
                $table->index('item_part_group_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laying_planning_parts');
    }
};
