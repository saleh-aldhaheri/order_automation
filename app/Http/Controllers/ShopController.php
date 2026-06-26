<?php

namespace App\Http\Controllers;

use App\Enums\ShopsEnum;
use App\Models\Shop;
use App\Services\ShopAuthService;
use App\Services\ShopService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(
        public ShopAuthService $shopAuthService,
        public ShopService $shopService
    ) {}

    public function index(Request $request)
    {
        [$perPage, $perPageOptions] = $this->resolvePagination($request);

        $search = $this->getSearch($request);

        $shops = $this->shopService->getShops($perPage, $search);

        return view('shops.index', [
            'shops' => $shops,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'search' => $search ?? '',
        ]);
    }

    public function show(Shop $shop)
    {
        return view('shops.view', [
            'shop' => $this->shopService->getShop($shop),
        ]);
    }

    public function redirect(string $type)
    {
        $url = $this->shopAuthService
            ->setShop(ShopsEnum::from($type))
            ->constructUrl();

        return redirect($url);
    }

    public function callback(Request $request, string $type)
    {
        $data =  $this->shopAuthService
            ->setShop(ShopsEnum::from($type))
            ->callback($request);

        ShopService::createShops($data);

        return 'we will decide later what we will return';
    }

    public function refresh(string $type, Shop $shop): Shop
    {
        $shop = $this->shopService
            ->setShop($shop)
            ->refreshAuthConfiguration();
        return $shop;
    }
}
