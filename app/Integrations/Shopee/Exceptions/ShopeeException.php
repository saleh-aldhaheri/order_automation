<?php

namespace App\Integrations\Shopee\Exceptions;

use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Throwable;

/**
 * The single error type for the Shopee integration.
 *
 * It extends Saloon's {@see RequestException} on purpose: that is the exception
 * type Saloon's send loop catches to drive retries, and the type
 * {@see \App\Integrations\Shopee\ShopeeClient::handleRetry()} acts on. If this
 * were a plain \Exception, a thrown ShopeeException would escape the retry catch
 * and the 401-refresh-retry flow would never run.
 *
 * Two construction shapes are supported:
 *  - HTTP failure: pass the {@see Response} so getResponse() works (used by the
 *    connector's getRequestException() + AlwaysThrowOnErrors).
 *  - Logical / config error (e.g. missing token when signing): no Response — it
 *    behaves like an ordinary exception. getResponse() must not be called in
 *    that case (handleRetry only reads it for HTTP failures).
 */
class ShopeeException extends RequestException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?Response $response = null,
    ) {
        if ($response instanceof Response) {
            parent::__construct($response, $message !== '' ? $message : null, $code, $previous);

            return;
        }

        \Exception::__construct($message, $code, $previous);
    }
}
