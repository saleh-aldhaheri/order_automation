<?php

namespace Database\States;

use App\Enums\PermissionsEnum;
use Spatie\Permission\Models\Permission;

class EnsurePermissionsSeeded
{
    public function __invoke()
    {
        if ($this->preset()) {
            return;
        }

        $permissions = array_map(
            fn ($case) => [
                'guard_name' => 'web',
                'name' => $case->value,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            PermissionsEnum::cases()
        );

        Permission::insert($permissions);
    }

    public function preset()
    {
        $permissions = collect(PermissionsEnum::cases())
            ->map(fn ($permission) => $permission->value)
            ->values()
            ->toArray();

        return Permission::whereIn('name', $permissions)->exists();
    }
}
