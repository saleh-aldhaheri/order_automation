<?php

namespace App\Services\Integrations\Contracts;

use App\Models\Provider;
use Illuminate\Http\Request;

interface ProviderContract
{
    public function constructAuthorizationUrl(): string;
    public function handleCallback(Request $request): array;
    public function refreshToken(): Provider;
}
