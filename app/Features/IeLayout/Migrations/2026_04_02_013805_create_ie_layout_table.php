<?php

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
        Schema::create('ie_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->float('price');
            $table->boolean('is_gl_number');
            $table->string('gl_number', 255)->nullable();
            $table->string('department', 255);
            $table->foreignId('created_by_id')->nullable()->constrained('users');
            $table->foreignId('updated_by_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ie_layouts');
    }

    // php artisan migrate --path=/app/Features/IeLayout/Migrations/2026_04_02_013805_create_ie_layout_table.php
};
