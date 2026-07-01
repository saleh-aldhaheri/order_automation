<?php

use App\Integrations\Shopee\Data\SearchPackageFilterData;
use App\Integrations\Shopee\Data\SearchPackageListData;
use App\Integrations\Shopee\Data\SearchPackageListItemData;
use App\Integrations\Shopee\Data\SearchPackagePaginationData;
use App\Integrations\Shopee\Data\SearchPackageSortData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Orders\SearchPackageList;
use App\Integrations\Shopee\ShopeeClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId = config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->accessToken = 'access-token';
    $this->shopId = 1;
    $this->pageSize = 20;
    $this->cursor = bin2hex(random_bytes(16));

    $this->request = new SearchPackageList(
        $this->pageSize,
    );

    $this->shopeeClient = new ShopeeClient(
        partnerId: $this->partnerId,
        partnerKey: $this->partnerKey,
        baseUrl: $this->baseUrl,
        accessToken: $this->accessToken,
        shopId: $this->shopId,
    );
});

describe('request', function () {
    it('builds the body with an empty filter when only pagination is given', function () {
        $body = $this->request->body()->all();

        expect($body['filter'])->toBeInstanceOf(stdClass::class)
            ->and($body['pagination'])->toBe(['page_size' => $this->pageSize])
            ->and($body)->not->toHaveKey('sort');
    });

    it('builds the body with filter, sort and cursor, stripping null fields', function () {
        $request = new SearchPackageList(
            pageSize: $this->pageSize,
            cursor: $this->cursor,
            filter: new SearchPackageFilterData(
                packageStatus: 2,
                logisticsChannelIds: [10001, 20002],
            ),
            sort: new SearchPackageSortData(
                sortType: 1,
                ascending: true,
            ),
        );

        expect($request->body()->all())->toBe([
            'filter' => [
                'package_status' => 2,
                'logistics_channel_ids' => [10001, 20002],
            ],
            'pagination' => [
                'page_size' => $this->pageSize,
                'cursor' => $this->cursor,
            ],
            'sort' => [
                'sort_type' => 1,
                'ascending' => true,
            ],
        ]);
    });

    it('uses the correct endpoint for the request', function () {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/order/search_package_list');
    });
});

describe('response', function () {
    it('casts the full dto, including the optional fields, from the response', function () {

        $requestMock = new MockClient([
            SearchPackageList::class => MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [
                    'packages_list' => [
                        [
                            'order_sn' => '201218V2Y6E59M',
                            'package_number' => 'PKG-1',
                            'logistics_channel_id' => 10001,
                            'product_location_id' => 'IDG',
                            'sorting_group' => 'group-a',
                            'is_shipment_arranged' => true,
                        ],
                        [
                            'order_sn' => '2404098R48U37H',
                            'package_number' => 'PKG-2',
                            'logistics_channel_id' => 20002,
                            'product_location_id' => 'IDH',
                            'sorting_group' => 'group-b',
                            'is_shipment_arranged' => false,
                        ],
                    ],
                    'pagination' => [
                        'total_count' => 2,
                        'next_cursor' => '20',
                        'more' => true,
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->searchPackageList($this->pageSize);

        expect($response)->toBeInstanceOf(SearchPackageListData::class)
            ->and($response->packagesList)->toHaveCount(2)
            ->and($response->packagesList[0])->toBeInstanceOf(SearchPackageListItemData::class)
            ->and($response->packagesList[0]->orderSn)->toBe('201218V2Y6E59M')
            ->and($response->packagesList[0]->packageNumber)->toBe('PKG-1')
            ->and($response->packagesList[0]->logisticsChannelId)->toBe(10001)
            ->and($response->packagesList[0]->productLocationId)->toBe('IDG')
            ->and($response->packagesList[0]->sortingGroup)->toBe('group-a')
            ->and($response->packagesList[0]->isShipmentArranged)->toBeTrue()
            ->and($response->packagesList[1]->orderSn)->toBe('2404098R48U37H')
            ->and($response->packagesList[1]->isShipmentArranged)->toBeFalse()
            ->and($response->pagination)->toBeInstanceOf(SearchPackagePaginationData::class)
            ->and($response->pagination->totalCount)->toBe(2)
            ->and($response->pagination->nextCursor)->toBe('20')
            ->and($response->pagination->more)->toBeTrue();
    });

    it('casts the dto when only the required fields are returned', function () {

        $requestMock = new MockClient([
            SearchPackageList::class => MockResponse::make([
                'response' => [
                    'packages_list' => [
                        [
                            'order_sn' => '201218V2Y6E59M',
                            'package_number' => 'PKG-1',
                        ],
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->searchPackageList($this->pageSize);

        expect($response)->toBeInstanceOf(SearchPackageListData::class)
            ->and($response->packagesList)->toHaveCount(1)
            ->and($response->packagesList[0])->toBeInstanceOf(SearchPackageListItemData::class)
            ->and($response->packagesList[0]->orderSn)->toBe('201218V2Y6E59M')
            ->and($response->packagesList[0]->packageNumber)->toBe('PKG-1')
            ->and($response->packagesList[0]->logisticsChannelId)->toBeNull()
            ->and($response->packagesList[0]->productLocationId)->toBeNull()
            ->and($response->packagesList[0]->sortingGroup)->toBeNull()
            ->and($response->packagesList[0]->isShipmentArranged)->toBeNull()
            ->and($response->pagination)->toBeNull();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $requestMock = new MockClient([
            SearchPackageList::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->searchPackageList($this->pageSize);
    })->throws(ShopeeException::class);

    it('throws a ShopeeException when the casting fails', function () {
        $requestMock = new MockClient([
            SearchPackageList::class => MockResponse::make([
                'response' => [
                    'packages_list' => [
                        ['order_sn' => '201218V2Y6E59M'], // missing the required package_number
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->searchPackageList($this->pageSize);
    })->throws(ShopeeException::class);
});
