<?php

namespace App\Data\Integrations\Shopee;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A single order from Shopee's get_order_detail `response.order_list`.
 *
 * Required properties below are the fields Shopee marks "Return by default".
 * Everything nullable is optional — it is only present when requested via the
 * `response_optional_fields` parameter, or is conditionally returned (pending,
 * advance fulfilment, cancellation, prescription, BR-only, etc.).
 */
#[MapInputName(SnakeCaseMapper::class)]
class GetOrderDetailsData extends Data
{
    /**
     * @param  array<int, OrderItemData>|null  $itemList
     * @param  array<int, PackageData>|null  $packageList
     * @param  array<string, mixed>|null  $invoiceData
     * @param  array<int, mixed>|null  $paymentInfo
     * @param  array<int, string>|null  $pendingTerms
     * @param  array<int, string>|null  $pendingDescription
     * @param  array<int, string>|null  $warning
     * @param  array<int, string>|null  $buyerProofOfCollection
     * @param  array<int, string>|null  $prescriptionImages
     */
    public function __construct(
        // --- Returned by default ---
        public string $orderSn,
        public string $region,
        public string $currency,
        public bool $cod,
        public string $orderStatus,
        public int $createTime,
        public int $updateTime,
        public int $daysToShip,
        public int $shipByDate,
        public ?string $messageToSeller = null,

        // --- Optional: requested via response_optional_fields ---
        public ?int $buyerUserId = null,
        public ?string $buyerUsername = null,
        public ?float $estimatedShippingFee = null,
        public ?RecipientAddressData $recipientAddress = null,
        public ?float $actualShippingFee = null,
        public ?bool $actualShippingFeeConfirmed = null,
        public ?bool $goodsToDeclare = null,
        public ?string $note = null,
        public ?int $noteUpdateTime = null,
        public ?int $payTime = null,
        public ?string $dropshipper = null,
        public ?string $dropshipperPhone = null,
        public ?bool $splitUp = null,
        public ?string $buyerCancelReason = null,
        public ?string $cancelBy = null,
        public ?string $cancelReason = null,
        public ?string $buyerCpfId = null,
        public ?string $fulfillmentFlag = null,
        public ?int $pickupDoneTime = null,
        public ?string $shippingCarrier = null,
        public ?string $checkoutShippingCarrier = null,
        public ?string $paymentMethod = null,
        public ?float $totalAmount = null,
        public ?float $reverseShippingFee = null,
        public ?int $orderChargeableWeightGram = null,
        public ?int $returnRequestDueDate = null,
        #[DataCollectionOf(OrderItemData::class)]
        public ?array $itemList = null,
        #[DataCollectionOf(PackageData::class)]
        public ?array $packageList = null,
        public ?array $invoiceData = null,
        public ?array $paymentInfo = null,
        // `edt` in the request maps to edt_from / edt_to in the response (BR only).
        public ?int $edtFrom = null,
        public ?int $edtTo = null,
        // `international_label` in the request maps to is_international (BR only).
        public ?bool $isInternational = null,

        // --- Conditionally returned ---
        public ?array $pendingTerms = null,
        public ?array $pendingDescription = null,
        public ?string $bookingSn = null,
        public ?bool $advancePackage = null,
        public ?bool $hotListingOrder = null,
        public ?bool $canFullCancelOrder = null,
        public ?bool $canPartialCancelOrder = null,
        public ?int $buyerPreferenceForPartialCancellation = null,
        public ?array $warning = null,
        public ?bool $isBuyerShopCollection = null,
        public ?array $buyerProofOfCollection = null,
        public ?int $prescriptionCheckStatus = null,
        public ?string $pharmacistName = null,
        public ?array $prescriptionImages = null,
        public ?int $prescriptionApprovalTime = null,
        public ?int $prescriptionRejectionTime = null,
        public ?string $prescriptionRejectReason = null,
    ) {}
}
