<?php

namespace Database\States;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use Spatie\Permission\Models\Role;

class EnsureRolesSeeded
{
    public function __invoke()
    {
        $this->prepareSuperAdmin();
        $this->prepareAdmin();
        $this->prepareUser();
    }

    public function prepareSuperAdmin()
    {
        if (Role::where('name', RolesEnum::SUPER_ADMIN->value)->exists()) {
            return;
        }

        $role = Role::create([
            'name' => RolesEnum::SUPER_ADMIN->value,
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo(PermissionsEnum::cases());
    }

    public function prepareAdmin()
    {
        if (Role::where('name', RolesEnum::ADMIN->value)->exists()) {
            return;
        }

        $role = Role::create([
            'name' => RolesEnum::ADMIN->value,
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo(RolesEnum::adminPermissions());
    }

    public function prepareUser()
    {
        if (Role::where('name', RolesEnum::User->value)->exists()) {
            return;
        }

        $role = Role::create([
            'name' => RolesEnum::User->value,
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo(RolesEnum::userPermissions());
    }
}
