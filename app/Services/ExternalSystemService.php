<?php

namespace App\Services;

use App\Models\ExternalSystem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ExternalSystemService
{
    public function getExternalSystems(int $perPage, ?string $search = null): LengthAwarePaginator
    {
        return ExternalSystem::query()
            ->search($search)
            ->orderBy('system_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function storeExternalSystem(string $systemName, bool $isActive): array
    {
        $secret = $this->newClientSecretPair();

        $externalSystem = ExternalSystem::create([
            'client_id' => $this->generateClientId(),
            'client_secret' => $secret['client_secret'],
            'system_name' => $systemName,
            'is_active' => $isActive,
        ]);

        return [
            'external_system' => $externalSystem,
            'plain_client_secret' => $secret['plain_client_secret'],
        ];
    }

    public function updateExternalSystem(ExternalSystem $externalSystem, string $systemName, bool $isActive): void
    {
        $externalSystem->update([
            'system_name' => $systemName,
            'is_active' => $isActive,
        ]);
    }

    public function generateAccessToken(ExternalSystem $externalSystem): string
    {
        $externalSystem->tokens()?->delete();

        return $externalSystem->createToken('external_system')->plainTextToken;
    }

    public function rotateClientSecret(ExternalSystem $externalSystem): array
    {
        $secret = $this->newClientSecretPair();

        $externalSystem->update([
            'client_secret' => $secret['client_secret'],
        ]);

        return [
            'plain_client_secret' => $secret['plain_client_secret'],
        ];
    }

    public function revokeAllTokens(ExternalSystem $externalSystem): void
    {
        $externalSystem->tokens()?->delete();
    }

    public function deleteExternalSystem(ExternalSystem $externalSystem): void
    {
        $externalSystem->delete();
    }

    public function generateClientId(): string
    {
        return (string) Str::uuid();
    }

    private function newClientSecretPair(): array
    {
        $plainText = Str::random(64);

        return [
            'plain_client_secret' => $plainText,
            'client_secret' => Hash::make($plainText),
        ];
    }
}
