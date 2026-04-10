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
        Schema::create('wip_export_quantities', function (Blueprint $col) {
            $col->uuid('id')->primary();
            $col->foreignUuid('lot_id')->constrained('lots')->onDelete('cascade');
            $col->integer('qty');
            $col->foreignId('created_by')->constrained('users');
            $col->foreignId('updated_by')->nullable()->constrained('users');
            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wip_export_quantities');
    }
};
