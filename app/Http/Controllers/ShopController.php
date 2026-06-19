<?php

namespace App\Http\Controllers;

use App\Enums\ShopsEnum;
use App\Models\Shop;
use App\Services\ShopService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(
        public ShopService $shopService
    ) {}

    public function redirect(string $type)
    {
        $url = $this->shopService
            ->setShop(ShopsEnum::from($type))
            ->url();

        return redirect($url);
    }

    public function callback(Request $request, string $type): array
    {
        $shop =  $this->shopService
            ->setShop(ShopsEnum::from($type))
            ->callback($request);

        return $shop;
    }

    public function refresh(string $type, Shop $shop): Shop
    {
        $shop = $this->shopService
            ->setShopFromModel($shop)
            ->refresh();

        return $shop;
    }
}
