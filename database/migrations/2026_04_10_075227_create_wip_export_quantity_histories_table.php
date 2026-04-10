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
        Schema::create('wip_export_quantity_histories', function (Blueprint $col) {
            $col->uuid('id')->primary();
            $col->foreignUuid('export_quantity_id')->constrained('wip_export_quantities')->onDelete('cascade');
            $col->integer('old_qty');
            $col->integer('new_qty');
            $col->foreignId('updated_by')->constrained('users');
            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wip_export_quantity_histories');
    }
};
