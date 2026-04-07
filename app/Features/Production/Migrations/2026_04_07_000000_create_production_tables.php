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
            $table->id();
            $table->date('production_date');
            $table->foreignId('line_id')->constrained('lines')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('production_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->onDelete('cascade');
            $table->string('gl_number', 100);
            $table->string('color', 100);
            $table->timestamps();
        });

        Schema::create('production_item_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_item_id')->constrained('production_items')->onDelete('cascade');
            $table->string('size_name', 20);
            $table->integer('qty_input')->default(0);
            $table->integer('qty_output')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_item_sizes');
        Schema::dropIfExists('production_items');
        Schema::dropIfExists('productions');
    }
};
