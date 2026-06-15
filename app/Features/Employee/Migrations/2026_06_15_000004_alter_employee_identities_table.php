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
        Schema::table('employee_identities', function (Blueprint $table) {
            $table->dropColumn('identity_type');
            $table->foreignId('identity_type_id')->after('employee_id')->constrained('identity_types')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_identities', function (Blueprint $table) {
            $table->dropForeign(['identity_type_id']);
            $table->dropColumn('identity_type_id');
            $table->string('identity_type')->after('employee_id');
        });
    }
};
