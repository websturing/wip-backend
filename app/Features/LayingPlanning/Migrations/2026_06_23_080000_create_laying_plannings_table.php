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
        if (!Schema::hasTable('laying_plannings')) {
            Schema::create('laying_plannings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('serial_number');
                $table->uuid('lot_id');
                $table->uuid('laying_planning_type_id');
                $table->uuid('laying_planning_parent_id')->nullable();
                $table->uuid('color_id');
                $table->uuid('fabric_id');
                $table->date('plan_date');
                $table->string('fabric_pattern');
                $table->boolean('is_combine')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('lot_id')->references('id')->on('lots')->onDelete('cascade');
                $table->foreign('laying_planning_type_id')->references('id')->on('laying_planning_types')->onDelete('cascade');
                $table->foreign('laying_planning_parent_id')->references('id')->on('laying_plannings')->onDelete('cascade');
                $table->foreign('color_id')->references('id')->on('colors')->onDelete('cascade');
                $table->foreign('fabric_id')->references('id')->on('fabrics')->onDelete('cascade');
                
                $table->index('lot_id');
                $table->index('laying_planning_type_id');
                $table->index('laying_planning_parent_id');
                $table->index('color_id');
                $table->index('fabric_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laying_plannings');
    }
};


