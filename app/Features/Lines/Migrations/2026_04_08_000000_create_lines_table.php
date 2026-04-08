<?php

namespace App\Features\Lines\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
