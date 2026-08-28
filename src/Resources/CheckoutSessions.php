<?php

namespace ShotPay\Sdk\Resources;

use ShotPay\Sdk\CheckoutSession;
use ShotPay\Sdk\Transport;

/**
 * The checkout session lifecycle, documented at `/docs/api/checkout-sessions/`.
 */
final class CheckoutSessions
{
    public function __construct(private readonly Transport $transport) {}

    /**
     * `POST /v1/sessions`. Pass `$idempotencyKey` to make a retry of this exact
     * call safe — replaying the same key with the same params returns the
     * original session rather than opening a second one. Requires a key with
     * the `checkout:write` scope.
     *
     * @param  array<string, mixed>  $params  order_reference, total_amount
     *                                        (integer minor units — 42900 for
     *                                        $429.00), return_url, cancel_url,
     *                                        and optionally customer_email,
     *                                        customer_state, and metadata —
     *                                        your own string key/value pairs,
     *                                        echoed back on the session and on
     *                                        the contract it becomes. Max 50
     *                                        keys; keys 40 chars, values 500.
     */
    public function create(array $params, ?string $idempotencyKey = null): CheckoutSession
    {
        $headers = [];

        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return CheckoutSession::fromArray(
            $this->transport->object('POST', 'v1/sessions', ['json' => $params, 'headers' => $headers]),
        );
    }

    /**
     * `GET /v1/checkout-sessions/{id}`. The server-to-server counterpart to
     * create — ask what happened to a session without waiting on a webhook.
     * Requires a key with the `checkout:read` scope.
     */
    public function retrieve(string $id): CheckoutSession
    {
        return CheckoutSession::fromArray(
            $this->transport->object('GET', 'v1/checkout-sessions/'.rawurlencode($id)),
        );
    }
}
