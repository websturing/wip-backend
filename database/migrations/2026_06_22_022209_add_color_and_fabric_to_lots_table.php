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
        Schema::table('lots', function (Blueprint $table) {
            $table->foreignUuid('color_id')->nullable()->constrained('colors')->onDelete('set null');
            $table->foreignUuid('fabric_id')->nullable()->constrained('fabrics')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropForeign(['color_id']);
            $table->dropForeign(['fabric_id']);
            $table->dropColumn(['color_id', 'fabric_id']);
        });
    }
};
