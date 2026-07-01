<?php

namespace App\Adapters\Contracts;

use App\Data\Integrations\Requests\GetOrderRequestData;
use App\Data\Integrations\Requests\HandleCallbackRequest;
use App\Data\Integrations\Requests\ShipPackageRequestData;
use App\Data\Integrations\Responses\DocumentFileData;
use App\Data\Integrations\Responses\DocumentTypeOptionsResponse;
use App\Data\Integrations\Responses\GetTokenResponseData;
use App\Data\Integrations\Responses\OrderResponse;
use App\Data\Integrations\Responses\PackageResponse;
use App\Data\Integrations\Responses\ShippingOptionsResponse;
use App\Enums\DocumentStatusEnum;
use App\Enums\ShopsEnum;
use App\Models\Package;
use App\Models\Shop;
use Illuminate\Support\Collection;

interface ShopAdapterContract
{
    /**
     * Factory for a shop-bound service instance.
     *
     * Required so the marketplace can be resolved generically via
     * {@see ShopsEnum::service()} as `$type->service()::make($shop)`.
     */
    public static function make(Shop $shop): self;

    public static function constructAuthorizationUrl(): string;

    /**
     * Exchange the OAuth callback for tokens (auth flow — no Shop yet).
     *
     * @return Collection<GetTokenResponseData>
     */
    public static function handleCallback(HandleCallbackRequest $callbackRequest): Collection;

    /**
     * Refresh the shop's tokens and return the updated auth configuration.
     * Each marketplace has its own authentication scheme.
     *
     * @return array<string, mixed>
     */
    public function refreshAuthConfiguration(): array;

    /**
     * @return Collection<int, OrderResponse>
     */
    public function getOrder(GetOrderRequestData $data): Collection;

    /**
     * Fetch just the parcels for the given order(s), as neutral DTOs.
     *
     * @return Collection<int, PackageResponse>
     */
    public function getOrderPackages(GetOrderRequestData $data): Collection;

    /**
     * Fetch the shipping options for a single package, as a neutral DTO.
     *
     * Each marketplace translates its own "shipping parameter" payload into the
     * vendor-agnostic {@see ShippingOptionsResponse} so callers stay marketplace-blind.
     */
    public function getShippingOptions(Package $package): ShippingOptionsResponse;

    /**
     * Arrange shipment for a package from the seller's neutral selection.
     * Returns true on success.
     */
    public function shipPackage(ShipPackageRequestData $data): bool;

    /**
     * Fetch a parcel's tracking number from the marketplace.
     *
     * Returns null when the 3PL hasn't assigned one yet (the caller should retry
     * Keep pooling to get the tracking number
     */
    public function getTrackingNumber(Package $package): ?string;

    /**
     * Fetch the document/waybill types a package can generate, as a neutral DTO.
     * The seller picks one of the returned types to feed into createDocument().
     */
    public function getDocumentType(Package $package): DocumentTypeOptionsResponse;

    /**
     * Ask the marketplace to start generating the document of the given type.
     * `$documentType` is one of the values from {@see DocumentTypeOptionsResponse}.
     * Returns true once the generation task was accepted.
     */
    public function createDocument(Package $package, string $documentType): bool;

    /**
     * Poll the marketplace for the document's generation status, mapped to the
     * neutral {@see DocumentStatusEnum} (READY = downloadable).
     */
    public function checkDocumentStatus(Package $package): DocumentStatusEnum;

    /**
     * Download the generated document as a neutral file (raw bytes + metadata).
     */
    public function downloadDocument(Package $package): DocumentFileData;
}
