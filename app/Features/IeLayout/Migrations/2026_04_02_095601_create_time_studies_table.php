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
        Schema::create('time_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ie_layout_id')->constrained('ie_layouts')->onDelete('cascade');
            $table->foreignId('operation_id')->constrained('operations')->onDelete('cascade');
            $table->string('handling_position', 255);
            $table->integer('length');
            $table->integer('sequence');
            $table->string('machine_type', 255);
            $table->float('machine_turn');
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
        Schema::dropIfExists('time_studies');
    }

    // php artisan migrate --path=/app/Features/IeLayout/Migrations/2026_04_02_095601_create_time_studies_table.php
};
