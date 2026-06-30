<?php

use App\Integrations\Shopee\Data\GetOrderDetailsData;
use App\Integrations\Shopee\Data\OrderItemData;
use App\Integrations\Shopee\Data\PackageData;
use App\Integrations\Shopee\Data\RecipientAddressData;

it('constructs with all fields including the optional ones', function () {
    $data = new GetOrderDetailsData(
        orderSn: '201218V2Y6E59M',
        region: 'SG',
        currency: 'SGD',
        cod: false,
        orderStatus: 'READY_TO_SHIP',
        createTime: 1700000000,
        updateTime: 1700001000,
        daysToShip: 3,
        shipByDate: 1700300000,
        messageToSeller: 'Please pack carefully',
        buyerUserId: 999,
        buyerUsername: 'buyer_one',
        estimatedShippingFee: 4.50,
        recipientAddress: new RecipientAddressData(name: 'John Doe'),
        totalAmount: 42.50,
        itemList: [new OrderItemData(itemId: 100)],
        packageList: [new PackageData(packageNumber: 'PKG-1')],
        bookingSn: 'BOOK-1',
    );

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->region)->toBe('SG')
        ->and($data->cod)->toBeFalse()
        ->and($data->recipientAddress)->toBeInstanceOf(RecipientAddressData::class)
        ->and($data->itemList[0])->toBeInstanceOf(OrderItemData::class)
        ->and($data->packageList[0])->toBeInstanceOf(PackageData::class)
        ->and($data->packageList[0]->packageNumber)->toBe('PKG-1');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'order_sn' => '201218V2Y6E59M',
        'region' => 'SG',
        'currency' => 'SGD',
        'cod' => false,
        'order_status' => 'READY_TO_SHIP',
        'create_time' => 1700000000,
        'update_time' => 1700001000,
        'days_to_ship' => 3,
        'ship_by_date' => 1700300000,
        'message_to_seller' => 'Please pack carefully',
        'buyer_user_id' => 999,
        'buyer_username' => 'buyer_one',
        'estimated_shipping_fee' => 4.50,
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
        'actual_shipping_fee' => 5.00,
        'actual_shipping_fee_confirmed' => true,
        'goods_to_declare' => false,
        'note' => 'seller note',
        'note_update_time' => 1700002000,
        'pay_time' => 1700000500,
        'dropshipper' => 'DS Co',
        'dropshipper_phone' => '+65 9999 0000',
        'split_up' => false,
        'buyer_cancel_reason' => '',
        'cancel_by' => '',
        'cancel_reason' => '',
        'buyer_cpf_id' => '',
        'fulfillment_flag' => 'fulfilled_by_shopee',
        'pickup_done_time' => 1700200000,
        'shipping_carrier' => 'Standard Delivery',
        'checkout_shipping_carrier' => 'Standard Delivery',
        'payment_method' => 'Credit Card',
        'total_amount' => 42.50,
        'reverse_shipping_fee' => 0.0,
        'order_chargeable_weight_gram' => 500,
        'return_request_due_date' => 1700400000,
        'item_list' => [
            [
                'item_id' => 100,
                'item_name' => 'T-Shirt',
                'item_sku' => 'SKU-1',
                'model_id' => 200,
                'model_name' => 'Red / M',
                'model_sku' => 'SKU-1-RM',
                'model_quantity_purchased' => 2,
                'model_discounted_price' => 19.90,
                'model_original_price' => 29.90,
                'wholesale' => false,
                'weight' => 0.5,
                'order_item_id' => 5,
                'promotion_id' => 11,
                'promotion_type' => 'flash_sale',
                'promotion_group_id' => 7,
                'add_on_deal' => false,
                'add_on_deal_id' => 0,
                'main_item' => true,
                'is_b2c_owned_item' => false,
                'is_prescription_item' => false,
                'product_location_id' => ['IDG'],
                'image_info' => ['image_url' => 'https://cf.shopee.sg/file/abc'],
            ],
        ],
        'package_list' => [
            [
                'package_number' => 'PKG-1',
                'logistics_status' => 'LOGISTICS_READY',
                'logistics_channel_id' => 10001,
                'shipping_carrier' => 'Standard Delivery',
                'parcel_chargeable_weight_gram' => 500,
                'group_shipment_id' => 'GRP-1',
                'allow_self_design_awb' => false,
                'sorting_group' => 'group-a',
                'item_list' => null,
            ],
        ],
        'invoice_data' => ['number' => 'INV-1'],
        'payment_info' => ['method' => 'card'],
        'edt_from' => 1700500000,
        'edt_to' => 1700600000,
        'is_international' => false,
        'pending_terms' => ['term_a'],
        'pending_description' => ['desc_a'],
        'booking_sn' => 'BOOK-1',
        'advance_package' => false,
        'hot_listing_order' => false,
        'can_full_cancel_order' => true,
        'can_partial_cancel_order' => false,
        'buyer_preference_for_partial_cancellation' => 0,
        'warning' => ['be careful'],
        'is_buyer_shop_collection' => false,
        'buyer_proof_of_collection' => ['proof_a'],
        'prescription_check_status' => 1,
        'pharmacist_name' => 'Dr. Smith',
        'prescription_images' => ['rx_a'],
        'prescription_approval_time' => 1700700000,
        'prescription_rejection_time' => 0,
        'prescription_reject_reason' => '',
    ];

    $data = GetOrderDetailsData::from($input);

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->cod)->toBeFalse()
        ->and($data->totalAmount)->toBe(42.50)
        ->and($data->recipientAddress)->toBeInstanceOf(RecipientAddressData::class)
        ->and($data->recipientAddress->fullAddress)->toBe('123 Bedok North Ave')
        ->and($data->itemList[0])->toBeInstanceOf(OrderItemData::class)
        ->and($data->itemList[0]->itemName)->toBe('T-Shirt')
        ->and($data->packageList[0])->toBeInstanceOf(PackageData::class)
        ->and($data->packageList[0]->packageNumber)->toBe('PKG-1')
        ->and($data->toArray())->toEqual($input);
});
