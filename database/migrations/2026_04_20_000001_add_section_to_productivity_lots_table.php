<?php
/*
 * (c) Antigravity
 * Migration created to support work section filtering in productivity logging.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productivity_lots', function (Blueprint $table) {
            $table->string('section')->default('all')->after('lot_id');
        });
    }

    public function down(): void
    {
        Schema::table('productivity_lots', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
