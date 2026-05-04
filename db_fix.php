<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    if (!Schema::hasColumn('users', 'last_login_at')) {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('password');
            echo "Added 'last_login_at' column.\n";
        });
    }
    if (!Schema::hasColumn('users', 'status')) {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active')->after('last_login_at');
            echo "Added 'status' column.\n";
        });
    }
    echo "Database schema updated successfully.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
