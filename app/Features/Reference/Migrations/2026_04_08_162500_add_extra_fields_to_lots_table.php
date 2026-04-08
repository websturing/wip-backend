<?php

namespace App\Features\Reference\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->string('style_no')->nullable()->after('lot_code');
            $table->string('brand')->nullable()->after('style_no');
            $table->decimal('sam', 8, 3)->nullable()->after('brand');
            $table->date('delivery_date')->nullable()->after('sam');
            $table->date('order_date')->nullable()->after('delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn(['style_no', 'brand', 'sam', 'delivery_date', 'order_date']);
        });
    }
};
