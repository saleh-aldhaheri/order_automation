<?php

namespace App\Integrations\Shopee\Resources;

use App\Integrations\Shopee\Resource;

use App\Integrations\Shopee\Data\{
    GetShippingParameterData,
    GetTrackingNumberData,
    ShipOrderDropoffData,
    ShipOrderNonIntegratedData,
    ShipOrderPickupData,
    UpdateShippingPickupData,
};
use App\Integrations\Shopee\Requests\Logistics\{
    GetTrackingNumber,
    ShipOrder,
    UpdateShippingOrder,
    GetShippingParameter,
};

class Logistics extends Resource
{
    public function getShippingParameter(string $orderSn, ?string $packageNumber = null): GetShippingParameterData
    {
        return $this->connector->send(
            new GetShippingParameter($orderSn, $packageNumber)
        )->dtoOrFail();
    }

    /**
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

    public function getTrackingNumber(
        string $orderSn,
        ?string $packageNumber = null,
        ?string $responseOptionalFields = null
    ): GetTrackingNumberData {
        return  $this->connector->send(new GetTrackingNumber(
            $orderSn,
            $packageNumber,
            $responseOptionalFields
        ))->dtoOrFail();
    }
}
