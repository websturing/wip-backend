<?php

namespace App\Features\LayingPlanning\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laying_planning_detail_types')) {
            Schema::create('laying_planning_detail_types', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('detail_type');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index('detail_type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laying_planning_detail_types');
    }
};
