<?php

namespace App\Features\LayingPlanning\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laying_planning_detail_materials')) {
            Schema::create('laying_planning_detail_materials', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('laying_planning_detail_id');
                $table->uuid('laying_planning_detail_type_id')->nullable();
                $table->decimal('value_per_layer', 10, 3);
                $table->string('unit', 50);
                $table->uuid('color_id')->nullable();
                $table->uuid('fabric_id')->nullable();
                $table->json('properties')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('updated_by')->nullable()->constrained('users');
                $table->timestamps();

                $table->foreign('laying_planning_detail_id', 'lpdm_detail_fk')
                    ->references('id')
                    ->on('laying_planning_details')
                    ->onDelete('cascade');

                $table->foreign('laying_planning_detail_type_id', 'lpdm_type_fk')
                    ->references('id')
                    ->on('laying_planning_detail_types')
                    ->onDelete('set null');

                $table->foreign('color_id', 'lpdm_color_fk')
                    ->references('id')
                    ->on('colors')
                    ->onDelete('set null');

                $table->foreign('fabric_id', 'lpdm_fabric_fk')
                    ->references('id')
                    ->on('fabrics')
                    ->onDelete('set null');

                $table->unique(['laying_planning_detail_id', 'laying_planning_detail_type_id'], 'lpdm_detail_type_unique');
                $table->index('laying_planning_detail_id', 'lpdm_detail_idx');
                $table->index('laying_planning_detail_type_id', 'lpdm_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laying_planning_detail_materials');
    }
};
