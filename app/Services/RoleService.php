<?php

namespace App\Services;

use App\Enums\RolesEnum;
use App\Support\PermissionLabel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function getRoles(int $perPage, ?string $search = null): LengthAwarePaginator
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', [RolesEnum::SUPER_ADMIN->value, RolesEnum::ADMIN->value])
            ->when($search, function ($q) use ($search) {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $q->where('name', 'like', $term);
            })
            ->withCount('permissions')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function storeRole(string $name, array $permissionIds): void
    {
        DB::transaction(function () use ($name, $permissionIds) {
            $role = Role::create([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissionIds);
        });
    }

    public function updateRole(Role $role, string $name, array $permissionIds): void
    {
        DB::transaction(function () use ($role, $name, $permissionIds) {
            $role->update([
                'name' => $name,
            ]);
            $role->syncPermissions($permissionIds);
        });
    }

    public function deleteRole(Role $role): void
    {
        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                '_role' => [__('Cannot delete a role that is still assigned to users.')],
            ]);
        }

        $role->delete();
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    public function permissionsGrouped(): Collection
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        $grouped = $permissions->groupBy(
            fn (Permission $p) => PermissionLabel::resourceGroupKey($p->name)
        );

        return $grouped
            ->sortKeysUsing(function (string $a, string $b): int {
                if ($a === '__general__') {
                    return 1;
                }
                if ($b === '__general__') {
                    return -1;
                }

                return strcmp($a, $b);
            })
            ->map(
                fn (Collection $group) => $group->sortBy(fn (Permission $p) => $p->name)->values()
            );
    }
}
