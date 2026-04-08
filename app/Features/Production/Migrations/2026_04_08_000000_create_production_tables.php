<?php

namespace App\Features\Production\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('productions')) {
            Schema::create('productions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('line_id');
                $table->date('production_date');
                $table->timestamps();

                $table->foreign('line_id')->references('id')->on('lines')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('production_items')) {
            Schema::create('production_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('production_id');
                $table->uuid('lot_id');
                $table->string('color');
                $table->timestamps();

                $table->foreign('production_id')->references('id')->on('productions')->onDelete('cascade');
                $table->foreign('lot_id')->references('id')->on('lots')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('production_item_details')) {
            Schema::create('production_item_details', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('production_item_id');
                $table->string('size_name');
                $table->integer('qty_input')->default(0);
                $table->integer('qty_output')->default(0);
                $table->timestamps();

                $table->foreign('production_item_id')->references('id')->on('production_items')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_item_details');
        Schema::dropIfExists('production_items');
        Schema::dropIfExists('productions');
    }
};
