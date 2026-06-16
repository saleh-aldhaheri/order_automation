<?php

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->user = createUserAs();
    $this->users = User::factory(10)->create();
});

/**
 * Super admin role loses one permission; actor’s permission cache is cleared.
 */
function stripActorPermission(string $permissionValue): void
{
    $role = Role::where('name', RolesEnum::SUPER_ADMIN->value)->first();
    $role->revokePermissionTo($permissionValue);
    test()->user->forgetCachedPermissions();
}

describe('index', function () {
    it('returns 200 and renders the users index view with a paginated list', function () {
        actingAs($this->user)
            ->get(route('users.index'))
            ->assertOk()
            ->assertViewIs('users.index')
            ->assertViewHas('users');
    });

    it('returns 403 when the actor lacks user:view', function () {
        stripActorPermission(PermissionsEnum::USER_VIEW->value);

        actingAs($this->user)
            ->get(route('users.index'))
            ->assertForbidden();
    });
});

describe('show', function () {
    it('returns 200 and renders the user detail view', function () {
        $subject = $this->users->first();

        actingAs($this->user)
            ->get(route('users.show', $subject))
            ->assertOk()
            ->assertViewIs('users.show')
            ->assertViewHas('user', fn ($u) => $u->is($subject));
    });

    it('returns 403 when the actor lacks user:view', function () {
        stripActorPermission(PermissionsEnum::USER_VIEW->value);

        actingAs($this->user)
            ->get(route('users.show', $this->users->first()))
            ->assertForbidden();
    });
});

describe('create', function () {
    it('returns 200 and renders the create user form', function () {
        actingAs($this->user)
            ->get(route('users.create'))
            ->assertOk()
            ->assertViewIs('users.create');
    });

    it('returns 403 when the actor lacks user:view', function () {
        stripActorPermission(PermissionsEnum::USER_VIEW->value);

        actingAs($this->user)
            ->get(route('users.create'))
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks user:create', function () {
        stripActorPermission(PermissionsEnum::USER_CREATE->value);

        actingAs($this->user)
            ->get(route('users.create'))
            ->assertForbidden();
    });
});

describe('store', function () {
    it('creates a user, persists the row, and redirects to the users index', function () {
        $email = 'invited-'.uniqid('', true).'@example.com';
        $roleId = Role::findByName(RolesEnum::User->value, 'web')->id;

        actingAs($this->user)
            ->post(route('users.store'), [
                'name' => 'Invited User',
                'email' => $email,
                'role' => $roleId,
            ])
            ->assertRedirect(route('users.index'));

        assertDatabaseHas('users', ['email' => $email, 'name' => 'Invited User']);
    });

    it('returns 403 when the actor lacks user:view', function () {
        stripActorPermission(PermissionsEnum::USER_VIEW->value);

        actingAs($this->user)
            ->post(route('users.store'), [
                'name' => 'No Store',
                'email' => 'nostore-'.uniqid('', true).'@example.com',
                'role' => Role::findByName(RolesEnum::User->value, 'web')->id,
            ])
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks user:create', function () {
        stripActorPermission(PermissionsEnum::USER_CREATE->value);

        actingAs($this->user)
            ->post(route('users.store'), [
                'name' => 'No Create',
                'email' => 'nocreate-'.uniqid('', true).'@example.com',
                'role' => Role::findByName(RolesEnum::User->value, 'web')->id,
            ])
            ->assertForbidden();
    });

    it('redirects back with validation errors when the payload is invalid', function () {
        actingAs($this->user)
            ->post(route('users.store'), [
                'name' => 'x',
                'email' => 'not-an-email',
                'role' => 999999999,
            ])
            ->assertSessionHasErrors(['name', 'email', 'role']);
    });
});

describe('edit', function () {
    it('returns 200 and renders the edit user form', function () {
        $subject = $this->users->first();

        actingAs($this->user)
            ->get(route('users.edit', $subject))
            ->assertOk()
            ->assertViewIs('users.edit')
            ->assertViewHas(['user', 'roles', 'selectedRoleId']);
    });

    it('returns 403 when the actor lacks user:view', function () {
        stripActorPermission(PermissionsEnum::USER_VIEW->value);

        actingAs($this->user)
            ->get(route('users.edit', $this->users->first()))
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks user:update', function () {
        stripActorPermission(PermissionsEnum::USER_UPDATE->value);

        actingAs($this->user)
            ->get(route('users.edit', $this->users->first()))
            ->assertForbidden();
    });
});

describe('update', function () {
    it('updates the user and redirects to the users index', function () {
        $subject = $this->users->first();
        $subject->assignRole(RolesEnum::User);
        $newEmail = 'updated-'.uniqid('', true).'@example.com';
        $roleId = Role::findByName(RolesEnum::User->value, 'web')->id;

        actingAs($this->user)
            ->put(route('users.update', $subject), [
                'name' => 'Updated Name',
                'email' => $newEmail,
                'role' => $roleId,
            ])
            ->assertRedirect(route('users.index'));

        assertDatabaseHas('users', [
            'id' => $subject->id,
            'name' => 'Updated Name',
            'email' => $newEmail,
        ]);
    });

    it('returns 403 when the actor lacks user:view', function () {
        stripActorPermission(PermissionsEnum::USER_VIEW->value);

        $subject = $this->users->first();

        actingAs($this->user)
            ->put(route('users.update', $subject), [
                'name' => 'X',
                'email' => $subject->email,
                'role' => Role::findByName(RolesEnum::User->value, 'web')->id,
            ])
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks user:update', function () {
        stripActorPermission(PermissionsEnum::USER_UPDATE->value);

        $subject = $this->users->first();

        actingAs($this->user)
            ->put(route('users.update', $subject), [
                'name' => 'X',
                'email' => $subject->email,
                'role' => Role::findByName(RolesEnum::User->value, 'web')->id,
            ])
            ->assertForbidden();
    });

    it('redirects back with validation errors when the payload is invalid', function () {
        $subject = $this->users->first();

        actingAs($this->user)
            ->put(route('users.update', $subject), [
                'name' => 'x',
                'email' => 'bad',
                'role' => 999999999,
            ])
            ->assertSessionHasErrors(['name', 'email', 'role']);
    });
});

describe('destroy', function () {
    it('deletes a normal user and redirects to the users index', function () {
        $victim = User::factory()->create();
        $victim->assignRole(RolesEnum::User);

        actingAs($this->user)
            ->delete(route('users.destroy', $victim))
            ->assertRedirect(route('users.index'));

        assertDatabaseMissing('users', ['id' => $victim->id]);
    });

    it('returns 403 when the actor lacks user:view', function () {
        stripActorPermission(PermissionsEnum::USER_VIEW->value);

        $victim = User::factory()->create();

        actingAs($this->user)
            ->delete(route('users.destroy', $victim))
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks user:delete', function () {
        stripActorPermission(PermissionsEnum::USER_DELETE->value);

        $victim = User::factory()->create();

        actingAs($this->user)
            ->delete(route('users.destroy', $victim))
            ->assertForbidden();
    });

    it('redirects with an error when the actor tries to delete their own account', function () {
        $actor = createUserAs(RolesEnum::ADMIN);

        actingAs($actor)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $actor))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('user');
    });

    it('redirects with an error when the target has the Super Admin role', function () {
        actingAs($this->user)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $this->user))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('user');
    });
});

describe('sendEmailVerification', function () {
    it('sends a verification notification and redirects back to the user profile', function () {
        Notification::fake();

        $subject = User::factory()->unverified()->create();
        $subject->assignRole(RolesEnum::User);

        actingAs($this->user)
            ->post(route('users.send-email-verification', $subject))
            ->assertRedirect(route('users.show', $subject));

        Notification::assertSentTo($subject, VerifyEmail::class);
    });

    it('returns 403 when the actor lacks user:view', function () {
        stripActorPermission(PermissionsEnum::USER_VIEW->value);

        $subject = User::factory()->unverified()->create();

        actingAs($this->user)
            ->post(route('users.send-email-verification', $subject))
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks send:invitation', function () {
        stripActorPermission(PermissionsEnum::SEND_INVITATION->value);

        $subject = User::factory()->unverified()->create();

        actingAs($this->user)
            ->post(route('users.send-email-verification', $subject))
            ->assertForbidden();
    });

    it('redirects with an error when the email is already verified', function () {
        Notification::fake();

        $subject = $this->users->first();

        actingAs($this->user)
            ->from(route('users.show', $subject))
            ->post(route('users.send-email-verification', $subject))
            ->assertRedirect(route('users.show', $subject))
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    });
});

describe('resendInvitation', function () {
    it('sends the invitation notification and redirects back to the user profile', function () {
        Notification::fake();

        $subject = User::factory()->unverified()->create();
        $subject->assignRole(RolesEnum::User);

        actingAs($this->user)
            ->post(route('users.resend-invitation', $subject))
            ->assertRedirect(route('users.show', $subject));

        Notification::assertSentTo($subject, UserInvitation::class);
    });

    it('returns 403 when the actor lacks user:view', function () {
        stripActorPermission(PermissionsEnum::USER_VIEW->value);

        $subject = User::factory()->unverified()->create();

        actingAs($this->user)
            ->post(route('users.resend-invitation', $subject))
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks send:invitation', function () {
        stripActorPermission(PermissionsEnum::SEND_INVITATION->value);

        $subject = User::factory()->unverified()->create();

        actingAs($this->user)
            ->post(route('users.resend-invitation', $subject))
            ->assertForbidden();
    });

    it('redirects with an error when the email is already verified', function () {
        Notification::fake();

        $subject = $this->users->first();

        actingAs($this->user)
            ->from(route('users.show', $subject))
            ->post(route('users.resend-invitation', $subject))
            ->assertRedirect(route('users.show', $subject))
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    });
});
