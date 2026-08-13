<?php

namespace ShotPay\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use ShotPay\Sdk\Exceptions\ShotPayApiException;

/**
 * A thin wrapper around the checkout session lifecycle documented at
 * https://shotpay-platform.test/docs/api/ — open a session, retrieve one your
 * server opened, and (via `WebhookSignatureVerifier`) confirm a delivery is
 * genuine. Not a full API client: everything else in the widget API is called
 * directly, the same way this client is built.
 */
final class ShotPayClient
{
    private Client $http;

    public function __construct(
        private readonly string $clientId,
        private readonly string $apiKey,
        string $baseUrl = 'https://shotpay-platform.test/api',
    ) {
        $this->http = new Client([
            'base_uri' => rtrim($baseUrl, '/').'/',
            'headers' => [
                'X-Client-Id' => $this->clientId,
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * `POST /widget/sessions`. Pass `$idempotencyKey` to make a retry of this
     * exact call safe — replaying the same key with the same params returns
     * the original session rather than opening a second one.
     *
     * @param  array<string, mixed>  $params  order_reference, total_amount,
     *                                        return_url, cancel_url, and
     *                                        optionally customer_email,
     *                                        customer_state.
     */
    public function createCheckoutSession(array $params, ?string $idempotencyKey = null): CheckoutSession
    {
        $headers = [];

        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $this->call('POST', 'widget/sessions', ['json' => $params, 'headers' => $headers]);
    }

    /**
     * `GET /widget/checkout-sessions/{id}`. The server-to-server counterpart
     * to create — ask what happened to a session without waiting on a
     * webhook. Requires a key with the `widget:read` scope.
     */
    public function retrieveCheckoutSession(string $id): CheckoutSession
    {
        return $this->call('GET', 'widget/checkout-sessions/'.rawurlencode($id));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function call(string $method, string $path, array $options = []): CheckoutSession
    {
        try {
            $response = $this->http->request($method, $path, $options);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();

            if ($response === null) {
                throw $exception;
            }

            $body = json_decode((string) $response->getBody(), true);

            throw new ShotPayApiException($response->getStatusCode(), is_array($body) ? $body : []);
        }

        $body = json_decode((string) $response->getBody(), true);

        return CheckoutSession::fromArray($body['data']);
    }
}
