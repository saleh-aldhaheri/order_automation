<?php

namespace App\Integrations\Shopee\Resources;

use App\Integrations\Shopee\Data\CreateShippingDocumentOrderData;
use App\Integrations\Shopee\Data\CreateShippingDocumentResultData;
use App\Integrations\Shopee\Data\GetShippingDocumentResultOrderData;
use App\Integrations\Shopee\Data\GetShippingParameterData;
use App\Integrations\Shopee\Data\GetTrackingNumberData;
use App\Integrations\Shopee\Data\ShipOrderDropoffData;
use App\Integrations\Shopee\Data\ShipOrderNonIntegratedData;
use App\Integrations\Shopee\Data\ShipOrderPickupData;
use App\Integrations\Shopee\Data\ShippingDocumentOrderData;
use App\Integrations\Shopee\Data\ShippingDocumentParameterResultData;
use App\Integrations\Shopee\Data\ShippingDocumentResultData;
use App\Integrations\Shopee\Data\UpdateShippingPickupData;
use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;
use App\Integrations\Shopee\Requests\Logistics\Document\CreateShippingDocument;
use App\Integrations\Shopee\Requests\Logistics\Document\DownloadShippingDocument;
use App\Integrations\Shopee\Requests\Logistics\Document\GetShippingDocumentParameter;
use App\Integrations\Shopee\Requests\Logistics\Document\GetShippingDocumentResult;
use App\Integrations\Shopee\Requests\Logistics\GetShippingParameter;
use App\Integrations\Shopee\Requests\Logistics\GetTrackingNumber;
use App\Integrations\Shopee\Requests\Logistics\ShipOrder;
use App\Integrations\Shopee\Requests\Logistics\UpdateShippingOrder;
use App\Integrations\Shopee\Resource;
use Illuminate\Support\Collection;

class Logistics extends Resource
{
    /**
     * Check which shipping method (pickup / dropoff / non-integrated) a package
     * supports and what each one needs.
     */
    public function getShippingParameter(string $orderSn, ?string $packageNumber = null): GetShippingParameterData
    {
        return $this->connector->send(
            new GetShippingParameter($orderSn, $packageNumber)
        )->dtoOrFail();
    }

    /**
     * Arrange shipment for an order — it becomes PROCESSED.
     *
     * Pass exactly the method (pickup / dropoff / nonIntegrated) that
     * get_shipping_parameter's info_needed requires for this package.
     */
    public function shipOrder(
        string $orderSn,
        ?string $packageNumber = null,
        ?ShipOrderPickupData $pickup = null,
        ?ShipOrderDropoffData $dropoff = null,
        ?ShipOrderNonIntegratedData $nonIntegrated = null,
    ): bool {
        return $this->connector->send(
            new ShipOrder($orderSn, $packageNumber, $pickup, $dropoff, $nonIntegrated)
        )->dtoOrFail();
    }

    /**
     * Reschedule pickup (pickup only) — use when a pickup was wrong or failed.
     */
    public function updateShippingOrder(
        string $orderSn,
        UpdateShippingPickupData $pickup,
        ?string $packageNumber = null,
    ): bool {
        return $this->connector->send(
            new UpdateShippingOrder($orderSn, $pickup, $packageNumber)
        )->dtoOrFail();
    }

    /**
     * Get the tracking number for a package (may be empty until the 3PL assigns it).
     */
    public function getTrackingNumber(
        string $orderSn,
        ?string $packageNumber = null,
        ?string $responseOptionalFields = null
    ): GetTrackingNumberData {
        return $this->connector->send(new GetTrackingNumber(
            $orderSn,
            $packageNumber,
            $responseOptionalFields
        ))->dtoOrFail();
    }

    /**
     * Get the selectable + suggested waybill document type for each order.
     *
     * @param  array<int, ShippingDocumentOrderData>  $orderList
     * @return Collection<int, ShippingDocumentParameterResultData>
     */
    public function getShippingDocumentParameter(array $orderList): Collection
    {
        return $this->connector
            ->send(new GetShippingDocumentParameter($orderList))
            ->dtoOrFail();
    }

    /**
     * Start the waybill (AWB) task — call after a tracking number exists.
     *
     * @param  array<int, CreateShippingDocumentOrderData>  $orderList
     * @return Collection<int, CreateShippingDocumentResultData>
     */
    public function createShippingDocument(array $orderList): Collection
    {
        return $this->connector
            ->send(new CreateShippingDocument($orderList))
            ->dtoOrFail();
    }

    /**
     * Poll the waybill task status — downloadable only when status is READY.
     *
     * @param  array<int, GetShippingDocumentResultOrderData>  $orderList
     * @return Collection<int, ShippingDocumentResultData>
     */
    public function getShippingDocumentResult(array $orderList): Collection
    {
        return $this->connector
            ->send(new GetShippingDocumentResult($orderList))
            ->dtoOrFail();
    }

    /**
     * Download the waybill — returns the raw file bytes (PDF / HTML / ZIP, check
     * the format). Call only after the result status is READY.
     *
     * @param  array<int, ShippingDocumentOrderData>  $orderList
     */
    public function downloadShippingDocument(
        array $orderList,
        ?ShopeeShippingDocumentTypeEnum $shippingDocumentType = null,
    ): string {
        return $this->connector
            ->send(new DownloadShippingDocument($orderList, $shippingDocumentType))
            ->dtoOrFail();
    }
}
