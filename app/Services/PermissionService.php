<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function getPermissions(int $perPage, ?string $search = null): LengthAwarePaginator
    {
        return Permission::query()
            ->when($search, function ($query) use ($search) {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('guard_name', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function storePermission(string $name): void
    {
        Permission::create([
            'guard_name' => 'web',
            'name' => $name,
        ]);
    }

    public function updatePermission(Permission $permission, string $name): void
    {
        $permission->update([
            'name' => $name,
        ]);
    }

    public function deletePermission(Permission $permission): void
    {
        if ($permission->roles()->exists()) {
            throw ValidationException::withMessages([
                '_permission' => [__('This permission is still assigned to one or more roles.')],
            ]);
        }

        $permission->delete();
    }
}
