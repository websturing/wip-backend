<?php

namespace App\Features\Profile\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function getProfile()
    {
        return User::with('role')->find(Auth::id());
    }

    public function updateProfile(array $data)
    {
        $user = User::find(Auth::id());
        return $user->update($data);
    }

    public function changePassword(string $currentPassword, string $newPassword)
    {
        $user = User::find(Auth::id());

        if (!Hash::check($currentPassword, $user->password)) {
            throw new \Exception('Current password does not match.');
        }

        return $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }
}
