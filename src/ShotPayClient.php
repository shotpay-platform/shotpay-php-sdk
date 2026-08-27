<?php

namespace ShotPay\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use ShotPay\Sdk\Resources\CheckoutSessions;
use ShotPay\Sdk\Resources\Contracts;

/**
 * A client for the ShotPay API documented at
 * https://shotpay-platform.test/docs/api/ — the checkout session lifecycle
 * under `$checkoutSessions`, your agreements under `$contracts`, and (via
 * `WebhookSignatureVerifier`) confirmation that a delivery is genuine.
 * Endpoints without a resource here stay callable directly, the same way this
 * client is built.
 */
final class ShotPayClient
{
    public readonly CheckoutSessions $checkoutSessions;

    public readonly Contracts $contracts;

    /**
     * @param  HandlerStack|null  $handler  A test seam. Build it with
     *                                      `HandlerStack::create()` so the
     *                                      `http_errors` middleware still maps
     *                                      a 4xx onto an exception.
     */
    public function __construct(
        string $clientId,
        string $apiKey,
        string $baseUrl = 'https://sandbox.shotpay.com/api',
        ?HandlerStack $handler = null,
    ) {
        $transport = new Transport(new Client([
            'base_uri' => rtrim($baseUrl, '/').'/',
            'headers' => [
                'X-Client-Id' => $clientId,
                'X-Api-Key' => $apiKey,
                'Accept' => 'application/json',
            ],
            ...($handler === null ? [] : ['handler' => $handler]),
        ]));

        $this->checkoutSessions = new CheckoutSessions($transport);
        $this->contracts = new Contracts($transport);
    }

    /**
     * @deprecated 0.2.0 Use `$client->checkoutSessions->create()`. Removed in 0.3.0.
     *
     * @param  array<string, mixed>  $params
     */
    public function createCheckoutSession(array $params, ?string $idempotencyKey = null): CheckoutSession
    {
        return $this->checkoutSessions->create($params, $idempotencyKey);
    }

    /**
     * @deprecated 0.2.0 Use `$client->checkoutSessions->retrieve()`. Removed in 0.3.0.
     */
    public function retrieveCheckoutSession(string $id): CheckoutSession
    {
        return $this->checkoutSessions->retrieve($id);
    }
}
