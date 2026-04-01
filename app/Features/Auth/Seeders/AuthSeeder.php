<?php

namespace App\Features\Auth\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Auth\Models\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
       // Cek apakah user sudah ada agar tidak duplikat saat seeding ulang
        User::updateOrCreate(
            ['email' => 'admin@test.com'], // Identifier
            [
                'name' => 'Admin Test',
                'password' => Hash::make('password123'), // Password untuk login
                'email_verified_at' => now(),
            ]
        );

        echo "✅ User Test Berhasil Dibuat!\n";
        echo "📧 Email: admin@test.com\n";
        echo "🔑 Pass: password123\n";
    }

    // php artisan db:seed --class="App\Features\Auth\Seeders\AuthSeeder"
}
