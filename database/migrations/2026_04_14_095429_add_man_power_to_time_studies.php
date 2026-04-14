<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_studies', function (Blueprint $table) {
            $table->decimal('man_power', 8, 2)->default(0)->after('machine_turn');
        });
    }

    public function down(): void
    {
        Schema::table('time_studies', function (Blueprint $table) {
            $table->dropColumn('man_power');
        });
    }
};
