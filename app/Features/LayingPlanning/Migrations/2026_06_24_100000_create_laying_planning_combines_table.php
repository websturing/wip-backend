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
        if (!Schema::hasTable('laying_planning_combines')) {
            Schema::create('laying_planning_combines', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('combine_number')->unique();
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laying_planning_combines');
    }
};
