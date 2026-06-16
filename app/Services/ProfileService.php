<?php

namespace App\Services;

use App\Models\User;

class ProfileService
{
    public function updateProfile(User $user, string $name, string $email, ?string $plainPassword = null): void
    {
        $user->name = $name;
        $user->email = $email;

        if ($plainPassword !== null && $plainPassword !== '') {
            $user->password = $plainPassword;
        }

        $user->save();
    }
}
