<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    public function index(Request $request)
    {
        [$perPage, $perPageOptions] = $this->resolvePagination($request);
        $search = $this->getSearch($request);

        $permissions = $this->permissionService->getPermissions($perPage, $search);

        return view('permissions.index', [
            'permissions' => $permissions,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'search' => $search ?? '',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);

        $this->permissionService->storePermission($validated['name']);

        return redirect()
            ->route('permissions.index')
            ->with('success', __('Permission created.'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permission->id),
            ],
        ]);

        $this->permissionService->updatePermission($permission, $validated['name']);

        return redirect()
            ->route('permissions.index')
            ->with('success', __('Permission updated.'));
    }

    public function destroy(Permission $permission)
    {
        $this->permissionService->deletePermission($permission);

        return redirect()
            ->route('permissions.index')
            ->with('success', __('Permission removed.'));
    }
}
