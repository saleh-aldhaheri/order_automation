<?php

use App\Enums\RolesEnum;
use App\Models\User;
use App\Notifications\UserInvitation;
use App\Services\UserService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = createUserAs();
    $this->userService = new UserService;
    User::factory()->count(10)->create();
});

describe('getUsers', function () {

    it('returns paginated users ordered by name', function () {
        $result = $this->userService->getUsers(5);
        expect($result)
            ->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBe(5);
    });

    it('should not return super admin user', function () {

        $superAdminId = $this->user->id;

        $result = $this->userService->getUsers(11);

        $ids = $result->pluck('id');

        expect($result)
            ->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBe(10)
            ->and($ids)->not->toContainEqual($superAdminId);
    });

    it('should search by user name', function () {
        $user = User::factory(1)->create(['name' => 'saleh'])->first();

        $result = $this->userService->getUsers(11, 'saleh');

        expect($result)
            ->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBe(1)
            ->and($result->first()->id)->toBe($user->id);
    });

    it('should search by user email', function () {
        $user = User::factory(1)->create(['name' => 'saleh'])->first();

        $result = $this->userService->getUsers(11, $user->email);

        expect($result)
            ->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBe(1)
            ->and($result->first()->id)->toBe($user->id);
    });

    it('should not search admin by name', function () {

        $result = $this->userService->getUsers(11, $this->user->name);

        expect($result)
            ->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBe(0)
            ->and($result->first())->toBeNull();
    });

    it('should not search admin by email', function () {

        $result = $this->userService->getUsers(11, $this->user->email);

        expect($result)
            ->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBe(0)
            ->and($result->first())->toBeNull();
    });
});

describe('storeUser', function () {

    it('creates a user and assigns the given role', function () {
        $roleId = Role::findByName(RolesEnum::User->value, 'web')->id;
        $email = 'new-user-'.uniqid('', true).'@example.com';

        $user = $this->userService->storeUser('Stored User', $email, $roleId);

        expect($user->exists)->toBeTrue()
            ->and($user->email)->toBe($email)
            ->and($user->hasRole(RolesEnum::User))->toBeTrue();
    });
});

describe('editUser', function () {

    it('returns the user and assignable roles excluding admin and super admin', function () {
        $subject = User::factory()->create();
        $subject->assignRole(RolesEnum::User);

        [$user, $roles] = $this->userService->editUser($subject);

        $names = $roles->pluck('name')->all();

        expect($user->is($subject))->toBeTrue()
            ->and($names)->not->toContain(RolesEnum::ADMIN->value)
            ->and($names)->not->toContain(RolesEnum::SUPER_ADMIN->value)
            ->and($names)->toContain(RolesEnum::User->value);
    });
});

describe('updateUser', function () {

    it('updates name, email, and role for an editable user', function () {
        $subject = User::factory()->create(['name' => 'Before', 'email' => 'before@example.com']);
        $subject->assignRole(RolesEnum::User);

        $adminRoleId = Role::findByName(RolesEnum::ADMIN->value, 'web')->id;
        $newEmail = 'after-'.uniqid('', true).'@example.com';

        $updated = $this->userService->updateUser($subject, 'After', $newEmail, $adminRoleId);

        expect($updated->name)->toBe('After')
            ->and($updated->email)->toBe($newEmail)
            ->and($updated->hasRole(RolesEnum::ADMIN))->toBeTrue()
            ->and($updated->hasRole(RolesEnum::User))->toBeFalse();
    });

    it('throws when the target is a super admin', function () {
        $roleId = Role::findByName(RolesEnum::User->value, 'web')->id;

        expect(fn () => $this->userService->updateUser($this->user, 'Nope', 'nope@example.com', $roleId))
            ->toThrow(ValidationException::class);
    });
});

describe('deleteUser', function () {

    it('deletes an editable user when the actor is another user', function () {
        $actor = createUserAs(RolesEnum::ADMIN);
        actingAs($actor);

        $victim = User::factory()->create();
        $victim->assignRole(RolesEnum::User);

        $this->userService->deleteUser($victim);

        expect(User::query()->find($victim->id))->toBeNull();
    });

    it('throws when attempting to delete your own account', function () {
        $actor = createUserAs(RolesEnum::ADMIN);
        actingAs($actor);

        expect(fn () => $this->userService->deleteUser($actor))
            ->toThrow(ValidationException::class);
    });

    it('throws when the target is a super admin', function () {
        $actor = createUserAs(RolesEnum::ADMIN);
        actingAs($actor);

        expect(fn () => $this->userService->deleteUser($this->user))
            ->toThrow(ValidationException::class);
    });
});

describe('SendEmailVerificationNotification', function () {

    it('sends the verification notification for an unverified editable user', function () {
        Notification::fake();

        $subject = User::factory()->unverified()->create();
        $subject->assignRole(RolesEnum::User);

        $this->userService->SendEmailVerificationNotification($subject);

        Notification::assertSentTo($subject, VerifyEmail::class);
    });

    it('throws when the email is already verified', function () {
        $subject = User::factory()->create();
        $subject->assignRole(RolesEnum::User);

        expect(fn () => $this->userService->SendEmailVerificationNotification($subject))
            ->toThrow(ValidationException::class);
    });

    it('throws when the target is a super admin', function () {
        expect(fn () => $this->userService->SendEmailVerificationNotification($this->user))
            ->toThrow(ValidationException::class);
    });
});

describe('resendInvitation', function () {

    it('sends the invitation for an unverified editable user', function () {
        Notification::fake();

        $subject = User::factory()->unverified()->create();
        $subject->assignRole(RolesEnum::User);

        $this->userService->resendInvitation($subject, new UserInvitation);

        Notification::assertSentTo($subject, UserInvitation::class);
    });

    it('throws when the email is already verified', function () {
        $subject = User::factory()->create();
        $subject->assignRole(RolesEnum::User);

        expect(fn () => $this->userService->resendInvitation($subject, new UserInvitation))
            ->toThrow(ValidationException::class);
    });

    it('throws when the target is a super admin', function () {
        expect(fn () => $this->userService->resendInvitation($this->user, new UserInvitation))
            ->toThrow(ValidationException::class);
    });
});
