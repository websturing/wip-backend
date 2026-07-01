<?php

namespace App\Features\LayingPlanning\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laying_planning_details')) {
            Schema::create('laying_planning_details', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('laying_planning_id');
                $table->uuid('laying_planning_detail_type_id')->nullable();
                $table->integer('table_number');
                $table->integer('layer_qty');
                $table->string('marker_code');
                $table->integer('marker_yard');
                $table->decimal('marker_inch', 8, 2);
                $table->decimal('allowance_inch', 8, 2);
                $table->boolean('is_pilot_run')->default(false);
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('updated_by')->nullable()->constrained('users');
                $table->timestamps();

                $table->foreign('laying_planning_id')
                    ->references('id')
                    ->on('laying_plannings')
                    ->onDelete('cascade');

                $table->foreign('laying_planning_detail_type_id')
                    ->references('id')
                    ->on('laying_planning_detail_types')
                    ->onDelete('set null');

                $table->index('laying_planning_id');
                $table->index('laying_planning_detail_type_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laying_planning_details');
    }
};
