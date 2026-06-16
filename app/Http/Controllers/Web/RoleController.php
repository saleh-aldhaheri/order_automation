<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService
    ) {}

    public function index(Request $request)
    {
        [$perPage, $perPageOptions] = $this->resolvePagination($request);
        $search = $this->getSearch($request);

        $roles = $this->roleService->getRoles($perPage, $search);

        return view('roles.index', [
            'roles' => $roles,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'search' => $search ?? '',
        ]);
    }

    public function create()
    {
        return view('roles.create', [
            'permissionsGrouped' => $this->roleService->permissionsGrouped(),
            'selectedIds' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = array_map('intval', $validated['permissions'] ?? []);

        $this->roleService->storeRole($validated['name'], $permissionIds);

        return redirect()
            ->route('roles.index')
            ->with('success', __('Role created.'));
    }

    public function edit(Role $role)
    {
        $role->loadMissing('permissions');

        return view('roles.edit', [
            'role' => $role,
            'permissionsGrouped' => $this->roleService->permissionsGrouped(),
            'selectedIds' => $role->permissions->pluck('id'),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = array_map('intval', $validated['permissions'] ?? []);

        $this->roleService->updateRole($role, $validated['name'], $permissionIds);

        return redirect()
            ->route('roles.index')
            ->with('success', __('Role updated.'));
    }

    public function destroy(Role $role)
    {
        $this->roleService->deleteRole($role);

        return redirect()
            ->route('roles.index')
            ->with('success', __('Role deleted.'));
    }
}
