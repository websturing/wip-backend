<?php

namespace App\Features\Production\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('line_id')->constrained('lines')->onDelete('cascade');
            $table->date('production_date');
            $table->timestamps();
        });

        Schema::create('production_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('production_id')->constrained('productions')->onDelete('cascade');
            $table->foreignUuid('lot_id')->constrained('lots')->onDelete('cascade');
            $table->string('color');
            $table->timestamps();
        });

        Schema::create('production_item_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('production_item_id')->constrained('production_items')->onDelete('cascade');
            $table->string('size_name');
            $table->integer('qty_input')->default(0);
            $table->integer('qty_output')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_item_details');
        Schema::dropIfExists('production_items');
        Schema::dropIfExists('productions');
    }
};
