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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path')->nullable();
            $table->string('icon')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->enum('platform', ['web', 'mobile', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('role_menus', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->primary(['role_id', 'menu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_menus');
        Schema::dropIfExists('menus');
    }
};
