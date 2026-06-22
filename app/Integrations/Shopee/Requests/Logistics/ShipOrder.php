<?php

namespace App\Integrations\Shopee\Requests\Logistics;

use App\Integrations\Shopee\Data\ShipOrderDropoffData;
use App\Integrations\Shopee\Data\ShipOrderNonIntegratedData;
use App\Integrations\Shopee\Data\ShipOrderPickupData;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use stdClass;

/**
 * Arrange shipment — order becomes PROCESSED. Call after get_shipping_parameter.
 *
 * Provide exactly the method (pickup / dropoff / non_integrated) that
 * get_shipping_parameter's `info_needed` requires; its key is included even when
 * empty ({}). Tracking number is NOT returned here — call get_tracking_number next.
 */
class ShipOrder extends Request
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $orderSn,
        public readonly ?string $packageNumber = null,
        public readonly ?ShipOrderPickupData $pickup = null,
        public readonly ?ShipOrderDropoffData $dropoff = null,
        public readonly ?ShipOrderNonIntegratedData $nonIntegrated = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/logistics/ship_order';
    }

    protected function defaultBody(): array
    {
        $body = ['order_sn' => $this->orderSn];

        if ($this->packageNumber) {
            $body['package_number'] = $this->packageNumber;
        }

        // Include the chosen method's key even when empty (API expects {}).
        if ($this->pickup) {
            $body['pickup'] = $this->objectOrEmpty($this->pickup->toArray());
        }

        if ($this->dropoff) {
            $body['dropoff'] = $this->objectOrEmpty($this->dropoff->toArray());
        }

        if ($this->nonIntegrated) {
            $body['non_integrated'] = $this->objectOrEmpty($this->nonIntegrated->toArray());
        }

        return $body;
    }

    public function createDtoFromResponse(Response $response): bool
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new RuntimeException($json['error']);
        }

        return true;
    }

    /**
     * Strip null fields; emit {} (not []) when nothing remains.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|stdClass
     */
    private function objectOrEmpty(array $data): array|stdClass
    {
        $filtered = array_filter($data, static fn($value): bool => $value !== null);

        return empty($filtered) ? new stdClass : $filtered;
    }
}
