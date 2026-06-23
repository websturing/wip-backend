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
        if (!Schema::hasTable('laying_planning_sizes')) {
            Schema::create('laying_planning_sizes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('laying_planning_id');
                $table->uuid('size_id');
                $table->integer('order_qty');
                $table->timestamps();

                $table->foreign('laying_planning_id')->references('id')->on('laying_plannings')->onDelete('cascade');
                $table->foreign('size_id')->references('id')->on('sizes')->onDelete('cascade');

                $table->index('laying_planning_id');
                $table->index('size_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laying_planning_sizes');
    }
};
