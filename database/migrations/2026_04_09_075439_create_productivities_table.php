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
        Schema::create('productivities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('line_id')->constrained('lines')->onDelete('cascade');
            $table->foreignUuid('lot_id')->constrained('lots')->onDelete('cascade');
            $table->date('date');
            
            $table->decimal('manpower', 8, 2)->default(0);
            $table->decimal('working_hour', 8, 2)->default(0);
            $table->decimal('smv', 8, 3)->default(0);
            $table->decimal('last_step', 8, 3)->default(0);

            $table->timestamps();

            // Unified constraint for daily entries per line and lot
            $table->unique(['line_id', 'lot_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productivities');
    }
};
