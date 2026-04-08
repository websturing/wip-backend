<?php

namespace App\Features\Lines\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Jika tabel sudah ada, kita cek apakah kolom ID nya sudah UUID (char 36)
        // Jika belum, kita drop dan buat ulang karena ini tabel master
        if (Schema::hasTable('lines')) {
            $columnType = DB::getSchemaBuilder()->getColumnType('lines', 'id');
            if ($columnType !== 'string' && $columnType !== 'guid' && $columnType !== 'char') {
                Schema::dropIfExists('lines');
            }
        }

        if (!Schema::hasTable('lines')) {
            Schema::create('lines', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('location')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lines');
    }
};
