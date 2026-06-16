<?php

use App\Models\ExternalSystem;
use App\Services\ExternalSystemService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->externalSystems = ExternalSystem::factory(10)->create();
    $this->externalSystemsService = new ExternalSystemService;
});

describe('getExternalSystems', function () {
    it('returns paginated external systems ordered by name', function (int $n) {
        $externalSystems = $this->externalSystemsService->getExternalSystems($n);
        $systemsNames = $externalSystems->getCollection()->pluck('system_name');

        expect($externalSystems)
            ->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($externalSystems->count())->toBe($n)
            ->and($systemsNames)
            ->toEqual($externalSystems->sortBy('system_name')->take($n)->pluck('system_name'));
    })->with([5, 3, 6]);

    it('filters external systems by system name', function (string $systemName) {

        ExternalSystem::factory()->create([
            'system_name' => $systemName,
        ]);

        $externalSystems = $this->externalSystemsService->getExternalSystems(10, $systemName);

        expect($externalSystems)
            ->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($externalSystems->count())->toBe(1)
            ->and($externalSystems->getCollection()->first()->system_name)
            ->toBe($systemName);
    })->with(['system1', 'system2', 'system3']);

    it('returns an empty page when no system matches the search', function () {

        $externalSystems = $this->externalSystemsService->getExternalSystems(10, '234324');

        expect($externalSystems)
            ->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($externalSystems->count())->toBe(0);
    });
});

describe('storeExternalSystem', function () {

    it('creates an external system and returns the plain client secret with the model', function () {
        $data = $this->externalSystemsService->storeExternalSystem('system1', true);

        assertDatabaseHas('external_systems', [
            'system_name' => 'system1',
        ]);

        expect($data)
            ->toHaveKeys(['external_system', 'plain_client_secret'])
            ->and($data['external_system'])
            ->toBeInstanceOf(ExternalSystem::class)
            ->and($data['plain_client_secret'])
            ->toBeString()
            ->not->toBeEmpty();
    });
});

describe('updateExternalSystem', function () {
    it('updates the system name and active flag', function () {
        $system = $this->externalSystems->first();

        $this->externalSystemsService->updateExternalSystem(
            $system,
            'updated-system-name',
            false,
        );

        $system->refresh();

        expect($system->system_name)->toBe('updated-system-name')
            ->and($system->is_active)->toBeFalse();

        assertDatabaseHas('external_systems', [
            'id' => $system->id,
            'system_name' => 'updated-system-name',
            'is_active' => false,
        ]);
    });

    it('does not change client credentials when updating metadata', function () {
        $system = $this->externalSystems->first();
        $clientId = $system->client_id;
        $clientSecret = $system->client_secret;

        $this->externalSystemsService->updateExternalSystem(
            $system,
            'renamed-only',
            true,
        );

        $system->refresh();

        expect($system->client_id)->toBe($clientId)
            ->and($system->client_secret)->toBe($clientSecret);
    });
});

describe('generateAccessToken', function () {
    it('generates an access token and revokes all previous tokens', function () {

        $system = $this->externalSystems->first();

        $system->createToken('old_token')->plainTextToken;
        $system->createToken('old_token')->plainTextToken;
        $system->createToken('old_token')->plainTextToken;

        expect($system->tokens()->count())
            ->toBe(3)
            ->and($system->tokens()->first()->name)
            ->toBe('old_token');

        $this->externalSystemsService->generateAccessToken($system);

        expect($system->tokens()->count())
            ->toBe(1)
            ->and($system->tokens()->first()->name)
            ->not->toBe('old_token')
            ->and($system->tokens()->first()->name)
            ->toBe('external_system');
    });
});

describe('revokeAllTokens', function () {
    it('revokes all tokens for the external system', function () {

        $system = $this->externalSystems->first();

        $system->createToken('old_token')->plainTextToken;
        $system->createToken('old_token')->plainTextToken;
        $system->createToken('old_token')->plainTextToken;

        expect($system->tokens()->count())
            ->toBe(3)
            ->and($system->tokens()->first()->name)
            ->toBe('old_token');

        $this->externalSystemsService->revokeAllTokens($system);

        expect($system->tokens()->count())
            ->toBe(0);
    });
});

describe('rotateClientSecret', function () {
    it('rotates the client secret and returns the plain client secret', function () {

        $system = $this->externalSystems->first();
        $oldHashedClientSecret = $system->client_secret;

        $data = $this->externalSystemsService->rotateClientSecret($system);

        expect($data)
            ->toHaveKey('plain_client_secret')
            ->and($data['plain_client_secret'])
            ->toBeString()
            ->and($system->client_secret)
            ->not->toBe($oldHashedClientSecret)
            ->and(Hash::check($data['plain_client_secret'], $system->client_secret))
            ->toBeTrue();
    });
});

describe('deleteExternalSystem', function () {
    it('deletes the external system', function () {

        $system = $this->externalSystems->first();

        $this->externalSystemsService->deleteExternalSystem($system);

        assertDatabaseMissing('external_systems', [
            'id' => $system->id,
            'system_name' => $system->system_name,
        ]);
    });
});
