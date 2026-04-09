<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ie_layouts', function (Blueprint $table) {
            $table->dropColumn(['gl_number', 'is_gl_number']);
        });
    }

    public function down(): void
    {
        Schema::table('ie_layouts', function (Blueprint $table) {
            $table->boolean('is_gl_number')->default(true);
            $table->string('gl_number')->nullable();
        });
    }
};
