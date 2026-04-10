<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('productions')) {
            Schema::table('productions', function (Blueprint $table) {
                if (!Schema::hasColumn('productions', 'remarks')) {
                    $table->text('remarks')->nullable();
                }
            });
        }

        if (Schema::hasTable('packings')) {
            Schema::table('packings', function (Blueprint $table) {
                if (!Schema::hasColumn('packings', 'remarks')) {
                    $table->text('remarks')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('productions')) {
            Schema::table('productions', function (Blueprint $table) {
                $table->dropColumn('remarks');
            });
        }

        if (Schema::hasTable('packings')) {
            Schema::table('packings', function (Blueprint $table) {
                $table->dropColumn('remarks');
            });
        }
    }
};
