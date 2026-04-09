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
        Schema::create('productivity_lots', function (Blueprint $table) {
            $table->id();
            $table->uuid('productivity_id');
            $table->uuid('lot_id');
            $table->timestamps();

            $table->foreign('productivity_id')->references('id')->on('productivities')->onDelete('cascade');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('cascade');
        });

        Schema::table('productivities', function (Blueprint $table) {
            $table->uuid('lot_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productivity_lots');
        
        Schema::table('productivities', function (Blueprint $table) {
            $table->uuid('lot_id')->nullable(false)->change();
        });
    }
};
