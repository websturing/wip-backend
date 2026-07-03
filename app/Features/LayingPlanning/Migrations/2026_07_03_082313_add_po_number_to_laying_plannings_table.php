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
        Schema::table('laying_plannings', function (Blueprint $table) {
            $table->string('po_number')->nullable()->after('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('laying_plannings', function (Blueprint $table) {
            $table->dropColumn('po_number');
        });
    }
};
