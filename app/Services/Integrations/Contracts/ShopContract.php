<?php

namespace App\Services\Integrations\Contracts;

use App\Models\Shop;
use Illuminate\Http\Request;

interface ShopContract
{
    public function constructAuthorizationUrl(): string;
    public function handleCallback(Request $request): array;
    public function refreshToken(): Shop;
}
