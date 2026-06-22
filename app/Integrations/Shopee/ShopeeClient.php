<?php

namespace App\Integrations\Shopee;

use Closure;
use Exception;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Traits\Plugins\HasTimeout;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Request;
use Saloon\Exceptions\Request\RequestException;
use App\Integrations\Shopee\Resources\{
    Authorization,
    Logistics,
    Orders
};

class ShopeeClient extends Connector
{
    use HasTimeout;

    public ?int $tries = 3;

    protected int $connectTimeout = 30;

    protected int $requestTimeout = 60;

    public ?int $retryInterval = 300;

    public ?bool $useExponentialBackoff = true;

    public function __construct(
        public readonly int $partnerId,
        public readonly string $partnerKey,
        public readonly string $baseUrl,
        protected ?string $accessToken = null,
        public readonly ?string $shopId = null,      // shop_id | merchant_id
        public ?string $refreshToken = null,
        private ?Closure $persistRefreshedToken =  null,
    ) {}

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function sign(string $path, int $timestamp, bool $isPublic): string
    {
        $base = $this->partnerId . $path . $timestamp;

        if (! $isPublic) {
            if (!$this->accessToken || !$this->shopId) {
                throw new Exception("access token or account id missing for a shop API call");
            }
            $base .= $this->accessToken . $this->shopId;
        }

        return hash_hmac('sha256', $base, $this->partnerKey);
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $path      = parse_url($pendingRequest->getUrl(), PHP_URL_PATH);
        $timestamp = time();
        $isPublic = $pendingRequest->getRequest()?->isPublic ?? false;

        $query = [
            'partner_id' => $this->partnerId,
            'timestamp'  => $timestamp,
        ];

        if (! $isPublic && $this->accessToken) {
            $query['access_token'] = $this->accessToken;
            $query['shop_id'] = $this->shopId;
        }

        $query['sign'] = $this->sign($path, $timestamp, $isPublic);

        $pendingRequest->query()->merge($query);
    }

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        if ($exception instanceof RequestException && $exception->getResponse()->status() === 401 && $this->refreshToken) {

            $this->refresh();

            return true;
        }

        return false;
    }

    public function refresh(): void
    {
        $refreshData = $this->authorization()->refreshAccessToken();

        $this->accessToken  = $refreshData->accessToken;
        $this->refreshToken = $refreshData->refreshToken;

        if ($this->persistRefreshedToken !== null) {
            ($this->persistRefreshedToken)($refreshData);
        }
    }

    public function authorization(): Authorization
    {
        return new Authorization($this);
    }

    public function order(): Orders
    {
        return new Orders($this);
    }

    public function logistic()
    {
        return new Logistics($this);
    }
}
