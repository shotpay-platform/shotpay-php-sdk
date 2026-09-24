<?php

use ShotPay\Sdk\Exceptions\ShotPayApiException;

function sessionPayload(array $overrides = []): array
{
    return [
        'id' => 'cs_test_123',
        'status' => 'pending',
        'order_reference' => 'ORD-1001',
        'ffl_required' => false,
        'total_amount' => 49999,
        'down_payment_amount' => 12500,
        'down_payment_percentage' => 25,
        'checkout_url' => 'https://sandbox.shotpay.com/c/cs_test_123',
        'return_url' => 'https://store.example/checkout/complete',
        'cancel_url' => 'https://store.example/cart',
        'expires_at' => '2026-08-26T12:00:00.000000Z',
        'created_at' => '2026-08-26T11:00:00.000000Z',
        'metadata' => [],
        ...$overrides,
    ];
}

test('create posts the params and maps the session', function () {
    $history = [];
    $client = sdkClient([jsonResponse(['data' => sessionPayload()])], $history);

    $session = $client->checkoutSessions->create([
        'order_reference' => 'ORD-1001',
        'total_amount' => 49999,
        'return_url' => 'https://store.example/checkout/complete',
        'cancel_url' => 'https://store.example/cart',
    ]);

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('POST')
        ->and($request->getUri()->getPath())->toBe('/api/v1/sessions')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and($session->id)->toBe('cs_test_123')
        ->and($session->totalAmount)->toBe(49999)
        ->and($session->checkoutUrl)->toBe('https://sandbox.shotpay.com/c/cs_test_123');
});

test('the idempotency key is sent only when one is supplied', function () {
    $history = [];
    $client = sdkClient([
        jsonResponse(['data' => sessionPayload()]),
        jsonResponse(['data' => sessionPayload()]),
    ], $history);

    $params = [
        'order_reference' => 'ORD-1001',
        'total_amount' => 49999,
        'return_url' => 'https://store.example/checkout/complete',
        'cancel_url' => 'https://store.example/cart',
    ];

    $client->checkoutSessions->create($params, 'a-uuid-per-attempt');
    $client->checkoutSessions->create($params);

    expect($history[0]['request']->getHeaderLine('Idempotency-Key'))->toBe('a-uuid-per-attempt')
        ->and($history[1]['request']->hasHeader('Idempotency-Key'))->toBeFalse();
});

test('retrieve gets the session by an encoded id', function () {
    $history = [];
    $client = sdkClient([jsonResponse(['data' => sessionPayload()])], $history);

    $client->checkoutSessions->retrieve('cs test/123');

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('GET')
        ->and($request->getUri()->getPath())->toBe('/api/v1/checkout-sessions/cs%20test%2F123');
});

test('the deprecated aliases reach the same endpoints', function () {
    $history = [];
    $client = sdkClient([
        jsonResponse(['data' => sessionPayload()]),
        jsonResponse(['data' => sessionPayload()]),
    ], $history);

    $client->createCheckoutSession([
        'order_reference' => 'ORD-1001',
        'total_amount' => 49999,
        'return_url' => 'https://store.example/checkout/complete',
        'cancel_url' => 'https://store.example/cart',
    ], 'a-uuid-per-attempt');
    $client->retrieveCheckoutSession('cs_test_123');

    expect($history[0]['request']->getUri()->getPath())->toBe('/api/v1/sessions')
        ->and($history[0]['request']->getHeaderLine('Idempotency-Key'))->toBe('a-uuid-per-attempt')
        ->and($history[1]['request']->getUri()->getPath())->toBe('/api/v1/checkout-sessions/cs_test_123');
});

test('a refused session raises the api exception', function () {
    $client = sdkClient([jsonResponse(['message' => 'Layaway is not available.'], 422)]);

    expect(fn () => $client->checkoutSessions->retrieve('cs_test_123'))
        ->toThrow(function (ShotPayApiException $exception) {
            expect($exception->status)->toBe(422)
                ->and($exception->getMessage())->toBe('Layaway is not available.');
        });
});
