<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ie_layouts', function (Blueprint $table) {
            $table->uuid('lot_id')->nullable()->after('id');
            // Assuming lots table exists and has uuid id
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('ie_layouts', function (Blueprint $table) {
            $table->dropForeign(['lot_id']);
            $table->dropColumn('lot_id');
        });
    }
};
