<?php
/*
 * (c) Antigravity
 * Migration created to support work section filtering in production logging.
 */

namespace App\Features\Production\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->string('section')->default('all')->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
