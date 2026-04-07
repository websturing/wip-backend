<?php

namespace App\Features\Reference\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('country')->nullable();
            $table->timestamps();
        });

        Schema::create('gl_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('gl_number');
            $table->timestamps();

            $table->unique(['customer_id', 'gl_number']);
            $table->index('customer_id');
        });

        Schema::create('lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gl_id')->constrained('gl_groups')->onDelete('cascade');
            $table->string('lot_number');
            $table->string('lot_code'); // Stored derived field
            $table->boolean('is_cancelled')->default(false);
            $table->timestamps();

            $table->unique(['gl_id', 'lot_number']);
            $table->index('gl_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
        Schema::dropIfExists('gl_groups');
        Schema::dropIfExists('customers');
    }
};
