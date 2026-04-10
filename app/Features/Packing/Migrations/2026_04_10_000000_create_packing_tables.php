<?php

namespace App\Features\Packing\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('packings')) {
            Schema::create('packings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->date('packing_date');
                $table->decimal('man_power', 8, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('packing_items')) {
            Schema::create('packing_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('packing_id');
                $table->uuid('lot_id');
                $table->string('color');
                $table->timestamps();

                $table->foreign('packing_id')->references('id')->on('packings')->onDelete('cascade');
                $table->foreign('lot_id')->references('id')->on('lots')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('packing_item_details')) {
            Schema::create('packing_item_details', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('packing_item_id');
                $table->string('size_name');
                $table->integer('qty_input')->default(0);
                $table->integer('qty_output')->default(0);
                $table->timestamps();

                $table->foreign('packing_item_id')->references('id')->on('packing_items')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_item_details');
        Schema::dropIfExists('packing_items');
        Schema::dropIfExists('packings');
    }
};
