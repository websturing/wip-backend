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
        Schema::create('colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gl_id')->constrained('gl_groups')->onDelete('cascade');
            $table->string('standard_name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('color_aliases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('color_id')->constrained('colors')->onDelete('cascade');
            $table->string('department');
            $table->string('alias_name');
            $table->timestamps();
        });

        Schema::create('fabrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gl_id')->constrained('gl_groups')->onDelete('cascade');
            $table->string('standard_content');
            $table->timestamps();
        });

        Schema::create('fabric_aliases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fabric_id')->constrained('fabrics')->onDelete('cascade');
            $table->string('department');
            $table->string('alias_content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fabric_aliases');
        Schema::dropIfExists('fabrics');
        Schema::dropIfExists('color_aliases');
        Schema::dropIfExists('colors');
    }
};
