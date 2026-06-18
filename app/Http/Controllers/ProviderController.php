<?php

namespace App\Http\Controllers;

use App\Enums\ProvidersEnum;
use App\Models\Provider;
use App\Services\ProviderService;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function __construct(
        public ProviderService $providerService
    ) {}

    public function redirect(string $type)
    {
        $url = $this->providerService
            ->setProvider(ProvidersEnum::from($type))
            ->url();

        return redirect($url);
    }

    public function callback(Request $request, string $type): array
    {
        $provider =  $this->providerService
            ->setProvider(ProvidersEnum::from($type))
            ->callback($request);

        return $provider;
    }

    public function refresh(string $type, Provider $provider): Provider
    {
        $provider = $this->providerService
            ->setProviderFromModel($provider)
            ->refresh();

        return $provider;
    }
}
