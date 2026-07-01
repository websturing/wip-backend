<?php

namespace App\Features\LayingPlanning\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laying_planning_detail_sizes')) {
            Schema::create('laying_planning_detail_sizes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('laying_planning_detail_id');
                $table->uuid('size_id');
                $table->integer('ratio_per_size');
                $table->timestamps();

                $table->foreign('laying_planning_detail_id')
                    ->references('id')
                    ->on('laying_planning_details')
                    ->onDelete('cascade');

                $table->foreign('size_id')
                    ->references('id')
                    ->on('sizes')
                    ->onDelete('cascade');

                $table->index('laying_planning_detail_id');
                $table->index('size_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laying_planning_detail_sizes');
    }
};
