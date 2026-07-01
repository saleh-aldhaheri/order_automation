<?php

use App\Integrations\Shopee\Data\GetOrderDetailsData;
use App\Integrations\Shopee\Data\GetOrderListData;
use App\Integrations\Shopee\Data\GetShipmentListData;
use App\Integrations\Shopee\Data\PackageDetailData;
use App\Integrations\Shopee\Data\SearchPackageListData;
use App\Integrations\Shopee\Data\SplitOrderData;
use App\Integrations\Shopee\Data\SplitOrderItemData;
use App\Integrations\Shopee\Data\SplitOrderPackageData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Orders\GetOrderDetail;
use App\Integrations\Shopee\Requests\Orders\GetOrderList;
use App\Integrations\Shopee\Requests\Orders\GetPackageDetail;
use App\Integrations\Shopee\Requests\Orders\GetShipmentList;
use App\Integrations\Shopee\Requests\Orders\SearchPackageList;
use App\Integrations\Shopee\Requests\Orders\SplitOrder;
use App\Integrations\Shopee\Requests\Orders\UnsplitOrder;
use App\Integrations\Shopee\Resource;
use App\Integrations\Shopee\Resources\Orders as OrdersResource;
use App\Integrations\Shopee\ShopeeClient;
use Illuminate\Support\Collection;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->shopeeAuthClient = new ShopeeClient(
        config('services.shopee.partner_id'),
        config('services.shopee.partner_key'),
        config('services.shopee.base_url'),
        accessToken: 'fake',
        shopId: 'fake',
        refreshToken: 'fake',
    );

    // Mock a single Shopee request class with the given body/status, then return
    // the Orders resource ready to call.
    $this->orders = function (string $requestClass, array $body, int $status = 200): OrdersResource {
        $this->shopeeAuthClient->withMockClient(new MockClient([
            $requestClass => MockResponse::make($body, $status),
        ]));

        return $this->shopeeAuthClient->order();
    };
});

it('resolves the Orders resource bound to the connector', function () {
    expect($this->shopeeAuthClient->order())
        ->toBeInstanceOf(Resource::class)
        ->toHaveProperty('connector');
});

describe('getOrderDetail', function () {
    it('returns a collection of GetOrderDetailsData on a valid response', function () {
        $orders = ($this->orders)(GetOrderDetail::class, [
            'response' => ['order_list' => [[
                'order_sn' => '2209ABC',
                'region' => 'SG',
                'currency' => 'SGD',
                'cod' => false,
                'order_status' => 'READY_TO_SHIP',
                'create_time' => 1660123127,
                'update_time' => 1660123127,
                'days_to_ship' => 3,
                'ship_by_date' => 1662209873,
            ]]],
        ]);

        $result = $orders->getOrderDetail(['2209ABC']);

        expect($result)->toBeInstanceOf(Collection::class)
            ->toHaveCount(1)
            ->and($result->first())->toBeInstanceOf(GetOrderDetailsData::class)
            ->and($result->first()->orderSn)->toBe('2209ABC')
            ->and($result->first()->orderStatus)->toBe('READY_TO_SHIP');
    });

    it('returns an empty collection when the response has no order_list', function () {
        $orders = ($this->orders)(GetOrderDetail::class, ['foo' => 'bar']);

        $result = $orders->getOrderDetail(['2209ABC']);

        expect($result)->toBeInstanceOf(Collection::class)->toBeEmpty();
    });

    it('throws ShopeeException when an order is missing required fields', function () {
        $orders = ($this->orders)(GetOrderDetail::class, [
            'response' => ['order_list' => [['order_sn' => '2209ABC']]], // missing region/currency/...
        ]);

        expect(fn () => $orders->getOrderDetail(['2209ABC']))->toThrow(ShopeeException::class);
    });

    it('throws ShopeeException when Shopee returns an error', function () {
        $orders = ($this->orders)(GetOrderDetail::class, [
            'error' => 'error_param',
            'message' => 'order_sn not found',
        ]);

        expect(fn () => $orders->getOrderDetail(['2209ABC']))->toThrow(ShopeeException::class);
    });
});

describe('getOrderList', function () {
    it('returns GetOrderListData on a valid response', function () {
        $orders = ($this->orders)(GetOrderList::class, [
            'response' => [
                'order_list' => [['order_sn' => '201ABC', 'order_status' => 'READY_TO_SHIP']],
                'more' => false,
                'next_cursor' => '',
            ],
        ]);

        $result = $orders->getOrderList('update_time', 1700000000, 1700086400);

        expect($result)->toBeInstanceOf(GetOrderListData::class)
            ->and($result->orderList)->toHaveCount(1)
            ->and($result->orderList[0]->orderSn)->toBe('201ABC')
            ->and($result->more)->toBeFalse();
    });

    it('returns an empty list when the response shape is unexpected', function () {
        $orders = ($this->orders)(GetOrderList::class, ['foo' => 'bar']);

        $result = $orders->getOrderList('update_time', 1700000000, 1700086400);

        expect($result)->toBeInstanceOf(GetOrderListData::class)
            ->and($result->orderList)->toBeEmpty();
    });

    it('throws ShopeeException when an order row is missing order_sn', function () {
        $orders = ($this->orders)(GetOrderList::class, [
            'response' => ['order_list' => [['order_status' => 'READY_TO_SHIP']]],
        ]);

        expect(fn () => $orders->getOrderList('update_time', 1700000000, 1700086400))
            ->toThrow(ShopeeException::class);
    });

    it('throws ShopeeException when Shopee returns an error', function () {
        $orders = ($this->orders)(GetOrderList::class, ['error' => 'error_auth', 'message' => 'bad token']);

        expect(fn () => $orders->getOrderList('update_time', 1700000000, 1700086400))
            ->toThrow(ShopeeException::class);
    });
});

describe('splitOrder', function () {
    $packageList = fn () => [new SplitOrderPackageData([new SplitOrderItemData(itemId: 123, modelId: 0)])];

    it('returns SplitOrderData on a valid response', function () use ($packageList) {
        $orders = ($this->orders)(SplitOrder::class, [
            'response' => [
                'order_sn' => '201ABC',
                'package_list' => [['package_number' => 'OFG1']],
            ],
        ]);

        $result = $orders->splitOrder('201ABC', $packageList());

        expect($result)->toBeInstanceOf(SplitOrderData::class)
            ->and($result->orderSn)->toBe('201ABC')
            ->and($result->packageList)->toHaveCount(1);
    });

    it('throws ShopeeException when the response is missing the required order_sn', function () use ($packageList) {
        $orders = ($this->orders)(SplitOrder::class, ['response' => ['package_list' => []]]);

        expect(fn () => $orders->splitOrder('201ABC', $packageList()))->toThrow(ShopeeException::class);
    });

    it('throws ShopeeException when Shopee returns an error', function () use ($packageList) {
        $orders = ($this->orders)(SplitOrder::class, ['error' => 'logistics.error', 'message' => 'cannot split']);

        expect(fn () => $orders->splitOrder('201ABC', $packageList()))->toThrow(ShopeeException::class);
    });
});

describe('unsplitOrder', function () {
    it('returns true on a successful response', function () {
        $orders = ($this->orders)(UnsplitOrder::class, ['response' => []]);

        expect($orders->unsplitOrder('201ABC'))->toBeTrue();
    });

    it('throws ShopeeException when Shopee returns an error', function () {
        $orders = ($this->orders)(UnsplitOrder::class, ['error' => 'logistics.error', 'message' => 'cannot unsplit']);

        expect(fn () => $orders->unsplitOrder('201ABC'))->toThrow(ShopeeException::class);
    });
});

describe('getShipmentList', function () {
    it('returns GetShipmentListData on a valid response', function () {
        $orders = ($this->orders)(GetShipmentList::class, [
            'response' => [
                'order_list' => [['order_sn' => '2003ABC', 'package_number' => '3801']],
                'more' => false,
                'next_cursor' => '',
            ],
        ]);

        $result = $orders->getShipmentList();

        expect($result)->toBeInstanceOf(GetShipmentListData::class)
            ->and($result->orderList)->toHaveCount(1)
            ->and($result->orderList[0]->orderSn)->toBe('2003ABC')
            ->and($result->orderList[0]->packageNumber)->toBe('3801');
    });

    it('throws ShopeeException when a row is missing the required package_number', function () {
        $orders = ($this->orders)(GetShipmentList::class, [
            'response' => ['order_list' => [['order_sn' => '2003ABC']]],
        ]);

        expect(fn () => $orders->getShipmentList())->toThrow(ShopeeException::class);
    });

    it('throws ShopeeException when Shopee returns an error', function () {
        $orders = ($this->orders)(GetShipmentList::class, ['error' => 'error_auth', 'message' => 'bad token']);

        expect(fn () => $orders->getShipmentList())->toThrow(ShopeeException::class);
    });
});

describe('searchPackageList', function () {
    it('returns SearchPackageListData on a valid response', function () {
        $orders = ($this->orders)(SearchPackageList::class, [
            'response' => [
                'packages_list' => [['order_sn' => '250ABC', 'package_number' => 'OFG9']],
                'pagination' => ['total_count' => 1, 'next_cursor' => '', 'more' => false],
            ],
        ]);

        $result = $orders->searchPackageList();

        expect($result)->toBeInstanceOf(SearchPackageListData::class)
            ->and($result->packagesList)->toHaveCount(1)
            ->and($result->packagesList[0]->orderSn)->toBe('250ABC')
            ->and($result->packagesList[0]->packageNumber)->toBe('OFG9');
    });

    it('throws ShopeeException when a package row is missing the required package_number', function () {
        $orders = ($this->orders)(SearchPackageList::class, [
            'response' => ['packages_list' => [['order_sn' => '250ABC']]],
        ]);

        expect(fn () => $orders->searchPackageList())->toThrow(ShopeeException::class);
    });

    it('throws ShopeeException when Shopee returns an error', function () {
        $orders = ($this->orders)(SearchPackageList::class, ['error' => 'error_param', 'message' => 'bad filter']);

        expect(fn () => $orders->searchPackageList())->toThrow(ShopeeException::class);
    });
});

describe('getPackageDetail', function () {
    it('returns a collection of PackageDetailData on a valid response', function () {
        $orders = ($this->orders)(GetPackageDetail::class, [
            'response' => ['package_list' => [[
                'order_sn' => '220ABC',
                'package_number' => 'OFG7',
                'fulfillment_status' => 'LOGISTICS_READY',
            ]]],
        ]);

        $result = $orders->getPackageDetail(['OFG7']);

        expect($result)->toBeInstanceOf(Collection::class)
            ->toHaveCount(1)
            ->and($result->first())->toBeInstanceOf(PackageDetailData::class)
            ->and($result->first()->orderSn)->toBe('220ABC')
            ->and($result->first()->packageNumber)->toBe('OFG7')
            ->and($result->first()->fulfillmentStatus)->toBe('LOGISTICS_READY');
    });

    it('returns an empty collection when the response has no package_list', function () {
        $orders = ($this->orders)(GetPackageDetail::class, ['foo' => 'bar']);

        expect($orders->getPackageDetail(['OFG7']))->toBeInstanceOf(Collection::class)->toBeEmpty();
    });

    it('throws ShopeeException when a package is missing required fields', function () {
        $orders = ($this->orders)(GetPackageDetail::class, [
            'response' => ['package_list' => [['order_sn' => '220ABC', 'package_number' => 'OFG7']]], // no fulfillment_status
        ]);

        expect(fn () => $orders->getPackageDetail(['OFG7']))->toThrow(ShopeeException::class);
    });

    it('throws ShopeeException when Shopee returns an error', function () {
        $orders = ($this->orders)(GetPackageDetail::class, ['error' => 'error_param', 'message' => 'package not found']);

        expect(fn () => $orders->getPackageDetail(['OFG7']))->toThrow(ShopeeException::class);
    });
});
