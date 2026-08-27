<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use ShotPay\Sdk\ShotPayClient;

/**
 * A client whose responses are scripted. `HandlerStack::create()` rather than
 * `new HandlerStack()` is load-bearing: it pushes the `http_errors` middleware,
 * which is what turns a mocked 4xx into the exception the client translates.
 *
 * @param  array<int, Response>  $responses
 * @param  array<int, array<string, mixed>>|null  $history  Filled with the requests that were sent.
 */
function sdkClient(array $responses, ?array &$history = null): ShotPayClient
{
    $stack = HandlerStack::create(new MockHandler($responses));

    if ($history !== null) {
        $stack->push(Middleware::history($history));
    }

    return new ShotPayClient('mch_test_1', 'sk_test_1', 'https://sandbox.shotpay.com/api', $stack);
}

/**
 * @param  array<string, mixed>  $body
 */
function jsonResponse(array $body, int $status = 200): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode($body));
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contractPayload(array $overrides = []): array
{
    return [
        'reference' => 'LAY-XK2M9QPR7V',
        'order_reference' => 'SO-2026-0042',
        'status' => 'active',
        'customer_name' => 'Dana Reyes',
        'customer_email' => 'dana@example.com',
        'customer_state' => 'CA',
        'total_amount' => 100000,
        'service_fee_amount' => 5000,
        'customer_total' => 105000,
        'down_payment_amount' => 26250,
        'paid_amount' => 26250,
        'remaining_amount' => 78750,
        'installments' => [
            [
                'sequence' => 1,
                'amount' => 26250,
                'due_date' => '2026-09-25',
                'status' => 'paid',
                'paid_at' => '2026-09-25T10:00:00.000000Z',
            ],
            [
                'sequence' => 2,
                'amount' => 26250,
                'due_date' => '2026-10-25',
                'status' => 'upcoming',
                'paid_at' => null,
            ],
        ],
        'started_at' => '2026-08-25T10:00:00.000000Z',
        'completed_at' => null,
        'defaulted_at' => null,
        'cancelled_at' => null,
        'created_at' => '2026-08-25T09:59:00.000000Z',
        'metadata' => ['sales_rep' => 'kim'],
        ...$overrides,
    ];
}
