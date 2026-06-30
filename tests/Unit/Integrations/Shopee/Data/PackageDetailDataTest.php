<?php

use App\Integrations\Shopee\Data\PackageDetailData;
use App\Integrations\Shopee\Data\PackageItemData;
use App\Integrations\Shopee\Data\RecipientAddressData;
use App\Integrations\Shopee\Data\StatusInfoTagData;

it('constructs with all fields including the optional ones', function () {
    $data = new PackageDetailData(
        orderSn: '201218V2Y6E59M',
        packageNumber: 'PKG-1',
        fulfillmentStatus: 'LOGISTICS_READY',
        updateTime: 1700001000,
        logisticsChannelId: 10001,
        shippingCarrier: 'Standard Delivery',
        allowSelfDesignAwb: false,
        daysToShip: 3,
        shipByDate: 1700300000,
        pendingTerms: ['term_a'],
        pendingDescription: ['desc_a'],
        trackingNumber: 'TRK-123',
        pickupDoneTime: 1700200000,
        isSplitUp: false,
        canSplitOrder: true,
        canUnsplitOrder: false,
        isShipmentArranged: true,
        isPreOrder: false,
        itemList: [new PackageItemData(itemId: 100, modelId: 200)],
        recipientAddress: new RecipientAddressData(name: 'John Doe'),
        statusInfoTag: new StatusInfoTagData(tagId: 1, timestamp: 1700000000),
        preparationEndTime: 1700250000,
        driverInfo: ['name' => 'Driver A'],
        canFullCancelOrder: true,
        canPartialCancelOrder: false,
    );

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->packageNumber)->toBe('PKG-1')
        ->and($data->fulfillmentStatus)->toBe('LOGISTICS_READY')
        ->and($data->isShipmentArranged)->toBeTrue()
        ->and($data->itemList[0])->toBeInstanceOf(PackageItemData::class)
        ->and($data->recipientAddress)->toBeInstanceOf(RecipientAddressData::class)
        ->and($data->recipientAddress->name)->toBe('John Doe')
        ->and($data->statusInfoTag)->toBeInstanceOf(StatusInfoTagData::class)
        ->and($data->driverInfo)->toBe(['name' => 'Driver A']);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'order_sn' => '201218V2Y6E59M',
        'package_number' => 'PKG-1',
        'fulfillment_status' => 'LOGISTICS_READY',
        'update_time' => 1700001000,
        'logistics_channel_id' => 10001,
        'shipping_carrier' => 'Standard Delivery',
        'allow_self_design_awb' => false,
        'days_to_ship' => 3,
        'ship_by_date' => 1700300000,
        'pending_terms' => ['term_a'],
        'pending_description' => ['desc_a'],
        'tracking_number' => 'TRK-123',
        'pickup_done_time' => 1700200000,
        'is_split_up' => false,
        'can_split_order' => true,
        'can_unsplit_order' => false,
        'is_shipment_arranged' => true,
        'is_pre_order' => false,
        'item_list' => [
            [
                'item_id' => 100,
                'model_id' => 200,
                'item_sku' => 'SKU-1',
                'model_quantity' => 3,
                'order_item_id' => 5,
                'product_location_id' => 'IDG',
                'promotion_group_id' => 7,
            ],
        ],
        'recipient_address' => [
            'name' => 'John Doe',
            'phone' => '+65 1234 5678',
            'town' => 'Bedok',
            'district' => 'East',
            'city' => 'Singapore',
            'state' => 'SG',
            'region' => 'SG',
            'zipcode' => '460123',
            'full_address' => '123 Bedok North Ave',
        ],
        'status_info_tag' => [
            'tag_id' => 1,
            'timestamp' => 1700000000,
        ],
        'preparation_end_time' => 1700250000,
        'driver_info' => ['name' => 'Driver A'],
        'can_full_cancel_order' => true,
        'can_partial_cancel_order' => false,
    ];

    $data = PackageDetailData::from($input);

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->fulfillmentStatus)->toBe('LOGISTICS_READY')
        ->and($data->isShipmentArranged)->toBeTrue()
        ->and($data->itemList[0])->toBeInstanceOf(PackageItemData::class)
        ->and($data->recipientAddress)->toBeInstanceOf(RecipientAddressData::class)
        ->and($data->recipientAddress->fullAddress)->toBe('123 Bedok North Ave')
        ->and($data->statusInfoTag->tagId)->toBe(1)
        ->and($data->toArray())->toEqual($input);
});
