<?php

namespace App\Http\Controllers\Web;

use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserInvitation;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request)
    {
        [$perPage, $perPageOptions] = $this->resolvePagination($request);
        $search = $this->getSearch($request);

        $users = $this->userService->getUsers($perPage, $search);

        return view('users.index', [
            'users' => $users,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'search' => $search ?? '',
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->whereNotIn('name', [
                    RolesEnum::ADMIN->value,
                    RolesEnum::SUPER_ADMIN->value,
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $this->userService->storeUser(
            $validated['name'],
            $validated['email'],
            (int) $validated['role'],
        );

        return redirect()
            ->route('users.index')
            ->with('success', __('User invited. They will receive an email to set a password.'));
    }

    public function show(User $user)
    {
        return view('users.show', [
            'user' => $user->loadMissing('roles', 'permissions'),
        ]);
    }

    public function edit(User $user)
    {
        [$user, $roles] = $this->userService->editUser($user);

        return view('users.edit', [
            'user' => $user,
            'roles' => $roles,
            'selectedRoleId' => $user->roles->first()?->id,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $this->userService->updateUser(
            $user,
            $validated['name'],
            $validated['email'],
            (int) $validated['role'],
        );

        return redirect()
            ->route('users.index')
            ->with('success', __('User updated.'));
    }

    public function destroy(User $user)
    {
        $this->userService->deleteUser($user);

        return redirect()
            ->route('users.index')
            ->with('success', __('User removed.'));
    }

    public function sendEmailVerification(User $user)
    {
        $this->userService->SendEmailVerificationNotification($user);

        return redirect()
            ->route('users.show', $user)
            ->with('success', __('Verification email sent.'));
    }

    public function resendInvitation(User $user)
    {
        $this->userService->resendInvitation($user, new UserInvitation);

        return redirect()
            ->route('users.show', $user)
            ->with('success', __('Invitation email sent.'));
    }
}
