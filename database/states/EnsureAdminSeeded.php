<?php

namespace Database\States;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EnsureAdminSeeded
{
    public function __invoke()
    {
        if ($this->present()) {
            return;
        }

        $user = User::create([
            'name' => 'super_admin',
            'email' => 'salehaldhaheri09@gmail.com',
            'password' => Hash::make('123Password'),
            'email_verified_at' => now(),
        ]);

        $user->assignRole(RolesEnum::SUPER_ADMIN);
    }

    public function present()
    {
        return User::role(RolesEnum::SUPER_ADMIN->value)->exists();
    }
}
