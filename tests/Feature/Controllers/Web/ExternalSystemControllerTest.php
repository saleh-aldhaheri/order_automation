<?php

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\ExternalSystem;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->user = createUserAs();
    $this->externalSystems = ExternalSystem::factory(5)->create();
});

function stripExternalSystemActorPermission(string $permissionValue): void
{
    $role = Role::where('name', RolesEnum::SUPER_ADMIN->value)->first();
    $role->revokePermissionTo($permissionValue);
    test()->user->forgetCachedPermissions();
}

describe('index', function () {
    it('returns 200 and renders the external systems index with pagination data', function () {
        actingAs($this->user)
            ->get(route('external-systems.index'))
            ->assertOk()
            ->assertViewIs('external-systems.index')
            ->assertViewHas('external_systems', fn ($paginator) => $paginator instanceof LengthAwarePaginator)
            ->assertViewHas('perPage')
            ->assertViewHas('perPageOptions')
            ->assertViewHas('search');
    });

    it('returns 403 when the actor lacks external_system:view', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_VIEW->value);

        actingAs($this->user)
            ->get(route('external-systems.index'))
            ->assertForbidden();
    });

    it('returns 403 when the actor is not a super admin', function () {
        $admin = createUserAs(RolesEnum::ADMIN);

        actingAs($admin)
            ->get(route('external-systems.index'))
            ->assertForbidden();
    });
});

describe('store', function () {
    it('creates an external system, redirects to the index, and flashes the plain client secret', function () {
        $name = 'new-system-'.uniqid('', true);

        actingAs($this->user)
            ->post(route('external-systems.store'), [
                'system_name' => $name,
                'is_active' => true,
            ])
            ->assertRedirect(route('external-systems.index'))
            ->assertSessionHas('success', __('System created.'))
            ->assertSessionHas('copy_client_secret');

        assertDatabaseHas('external_systems', [
            'system_name' => $name,
            'is_active' => true,
        ]);
    });

    it('returns 403 when the actor lacks external_system:view', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_VIEW->value);

        actingAs($this->user)
            ->post(route('external-systems.store'), [
                'system_name' => 'blocked-'.uniqid('', true),
                'is_active' => true,
            ])
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks external_system:create', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_CREATE->value);

        actingAs($this->user)
            ->post(route('external-systems.store'), [
                'system_name' => 'blocked-'.uniqid('', true),
                'is_active' => true,
            ])
            ->assertForbidden();
    });

    it('redirects back with validation errors when the payload is invalid', function () {
        actingAs($this->user)
            ->post(route('external-systems.store'), [
                'system_name' => 'x',
                'is_active' => 'not-a-boolean',
            ])
            ->assertSessionHasErrors(['system_name', 'is_active']);
    });

    it('redirects back with a validation error when the system name is not unique', function () {
        $existing = $this->externalSystems->first();

        actingAs($this->user)
            ->post(route('external-systems.store'), [
                'system_name' => $existing->system_name,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('system_name');
    });
});

describe('update', function () {
    it('updates the external system and redirects to the index', function () {
        $system = $this->externalSystems->first();
        $newName = 'renamed-'.uniqid('', true);

        actingAs($this->user)
            ->put(route('external-systems.update', $system), [
                'system_name' => $newName,
                'is_active' => false,
            ])
            ->assertRedirect(route('external-systems.index'))
            ->assertSessionHas('success', __('System updated.'));

        assertDatabaseHas('external_systems', [
            'id' => $system->id,
            'system_name' => $newName,
            'is_active' => false,
        ]);
    });

    it('allows keeping the same system name for the same record', function () {
        $system = $this->externalSystems->first();
        $name = $system->system_name;

        actingAs($this->user)
            ->put(route('external-systems.update', $system), [
                'system_name' => $name,
                'is_active' => false,
            ])
            ->assertRedirect(route('external-systems.index'));

        assertDatabaseHas('external_systems', [
            'id' => $system->id,
            'system_name' => $name,
            'is_active' => false,
        ]);
    });

    it('returns 403 when the actor lacks external_system:view', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_VIEW->value);

        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->put(route('external-systems.update', $system), [
                'system_name' => $system->system_name,
                'is_active' => true,
            ])
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks external_system:update', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_UPDATE->value);

        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->put(route('external-systems.update', $system), [
                'system_name' => $system->system_name,
                'is_active' => true,
            ])
            ->assertForbidden();
    });

    it('redirects back with validation errors when the payload is invalid', function () {
        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->put(route('external-systems.update', $system), [
                'system_name' => 'x',
                'is_active' => 'nope',
            ])
            ->assertSessionHasErrors(['system_name', 'is_active']);
    });

    it('redirects back when the system name collides with another record', function () {
        $a = $this->externalSystems->first();
        $b = $this->externalSystems->last();

        actingAs($this->user)
            ->put(route('external-systems.update', $a), [
                'system_name' => $b->system_name,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('system_name');
    });
});

describe('destroy', function () {
    it('deletes the external system and redirects to the index', function () {
        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->delete(route('external-systems.destroy', $system))
            ->assertRedirect(route('external-systems.index'))
            ->assertSessionHas('success', __('System Deleted.'));

        assertDatabaseMissing('external_systems', ['id' => $system->id]);
    });

    it('returns 403 when the actor lacks external_system:view', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_VIEW->value);

        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->delete(route('external-systems.destroy', $system))
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks external_system:delete', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_DELETE->value);

        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->delete(route('external-systems.destroy', $system))
            ->assertForbidden();
    });
});

describe('generateToken', function () {
    it('generates a token, redirects to the index, and flashes the plain token', function () {
        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->post(route('external-systems.generate-token', $system))
            ->assertRedirect(route('external-systems.index'))
            ->assertSessionHas('success', __('Token generated.'))
            ->assertSessionHas('copy_token');

        expect($system->fresh()->tokens()->count())->toBe(1)
            ->and($system->fresh()->tokens()->first()->name)->toBe('external_system');
    });

    it('returns 403 when the actor lacks external_system:view', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_VIEW->value);

        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->post(route('external-systems.generate-token', $system))
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks external_system:generate_token', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_GENERATE_TOKEN->value);

        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->post(route('external-systems.generate-token', $system))
            ->assertForbidden();
    });
});

describe('rotateClientSecret', function () {
    it('rotates the client secret, redirects, and flashes the new plain secret', function () {
        $system = $this->externalSystems->first();
        $oldSecret = $system->client_secret;

        actingAs($this->user)
            ->put(route('external-systems.rotate-client-secret', $system))
            ->assertRedirect(route('external-systems.index'))
            ->assertSessionHas('success', __('Client secret regenerated.'))
            ->assertSessionHas('copy_client_secret');

        expect($system->fresh()->client_secret)->not->toBe($oldSecret);
    });

    it('returns 403 when the actor lacks external_system:view', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_VIEW->value);

        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->put(route('external-systems.rotate-client-secret', $system))
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks external_system:rotate_secret', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_ROTATE_SECRET->value);

        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->put(route('external-systems.rotate-client-secret', $system))
            ->assertForbidden();
    });
});

describe('revokeToken', function () {
    it('revokes all tokens and redirects to the index', function () {
        $system = $this->externalSystems->first();
        $system->createToken('old')->plainTextToken;
        $system->createToken('old')->plainTextToken;

        expect($system->tokens()->count())->toBe(2);

        actingAs($this->user)
            ->post(route('external-systems.revoke-token', $system))
            ->assertRedirect(route('external-systems.index'))
            ->assertSessionHas('success', __('Token revoked.'));

        expect($system->fresh()->tokens()->count())->toBe(0);
    });

    it('returns 403 when the actor lacks external_system:view', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_VIEW->value);

        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->post(route('external-systems.revoke-token', $system))
            ->assertForbidden();
    });

    it('returns 403 when the actor lacks external_system:revoke_token', function () {
        stripExternalSystemActorPermission(PermissionsEnum::EXTERNAL_SYSTEM_REVOKE_TOKEN->value);

        $system = $this->externalSystems->first();

        actingAs($this->user)
            ->post(route('external-systems.revoke-token', $system))
            ->assertForbidden();
    });
});
