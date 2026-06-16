<?php

namespace App\Services;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserService
{
    public function getUsers(int $perPage, ?string $search = null): LengthAwarePaginator
    {
        return User::query()
            ->with('roles:name,id')
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', RolesEnum::SUPER_ADMIN->value);
            })
            ->search($search)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function storeUser(string $name, string $email, int $roleId): User
    {
        return DB::transaction(function () use ($name, $email, $roleId) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
            ]);

            $user->assignRole($roleId);

            return $user;
        });
    }

    public function editUser(User $user): array
    {
        $user->loadMissing('roles');

        return [
            $user,
            Role::query()
                ->whereNotIn('name', [RolesEnum::ADMIN->value, RolesEnum::SUPER_ADMIN->value])
                ->orderBy('name')
                ->get(),
        ];
    }

    public function updateUser(User $user, string $name, string $email, int $roleId): User
    {
        $this->ensureUserEditable($user);

        return DB::transaction(function () use ($user, $name, $email, $roleId) {
            $user->update([
                'name' => $name,
                'email' => $email,
            ]);

            $user->syncRoles([(int) $roleId]);

            return $user->fresh();
        });
    }

    public function deleteUser(User $user): void
    {
        $this->ensureUserEditable($user);

        if (Auth::user()->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => [__('You cannot delete your own account.')],
            ]);
        }

        $user->delete();
    }

    public function SendEmailVerificationNotification(User $user)
    {
        $this->ensureUserEditable($user);

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => [__('This user’s email is already verified.')],
            ]);
        }

        $user->sendEmailVerificationNotification();
    }

    public function resendInvitation(User $user, Notification $notification): void
    {
        $this->ensureUserEditable($user);

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => [__('This user’s email is already verified.')],
            ]);
        }

        $user->notify($notification);
    }

    private function ensureUserEditable(User $user): void
    {
        if ($user->hasRole(RolesEnum::SUPER_ADMIN)) {

            throw ValidationException::withMessages([
                'user' => [__('Cannot edit a user with the Super Admin role.')],
            ]);
        }
    }
}
