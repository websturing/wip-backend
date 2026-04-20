<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productivity_lots', function (Blueprint $table) {
            $table->uuid('media_id')->nullable()->after('lot_id');
            
            $table->foreign('media_id')
                ->references('id')
                ->on('media')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('productivity_lots', function (Blueprint $table) {
            $table->dropForeign(['media_id']);
            $table->dropColumn('media_id');
        });
    }
};
