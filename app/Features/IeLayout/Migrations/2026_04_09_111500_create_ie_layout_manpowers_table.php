<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ie_layout_manpowers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ie_layout_id')->constrained('ie_layouts')->onDelete('cascade');
            $table->date('date');
            $table->float('man_power_sewer');
            $table->float('man_power_matching');
            $table->float('man_power_qc');
            $table->float('man_power_others');
            $table->foreignId('created_by_id')->nullable()->constrained('users');
            $table->timestamps();

            // Unique constraint to prevent duplicate entries for the same day/layout
            $table->unique(['ie_layout_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ie_layout_manpowers');
    }
};
