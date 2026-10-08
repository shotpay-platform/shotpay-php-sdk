<?php

/**
 * Exercises this client against a real, locally-served shotpay-platform
 * instance — the thing every other test here mocks. Run by that app's own
 * `sdk-e2e-php` CI job, which boots itself, seeds a real merchant/key/contract
 * (database/seeders/E2eSdkCredentialsSeeder.php), and exports the
 * SHOTPAY_E2E_* vars these tests read. Without them, each test skips rather
 * than fails, so `vendor/bin/pest` stays green for anyone running it locally
 * with no server to hit.
 */

use ShotPay\Sdk\ShotPayClient;
use ShotPay\Sdk\WebhookSignatureVerifier;

function e2eConfigured(): bool
{
    return (string) getenv('SHOTPAY_E2E_BASE_URL') !== '';
}

function e2eClient(): ShotPayClient
{
    return new ShotPayClient(
        (string) getenv('SHOTPAY_E2E_CLIENT_ID'),
        (string) getenv('SHOTPAY_E2E_API_KEY'),
        (string) getenv('SHOTPAY_E2E_BASE_URL'),
    );
}

test('creates and retrieves a real checkout session', function () {
    $client = e2eClient();

    $session = $client->checkoutSessions->create([
        'order_reference' => 'E2E-'.bin2hex(random_bytes(4)),
        'total_amount' => 49999,
        'customer_state' => 'TX',
        'return_url' => 'https://example.test/return',
        'cancel_url' => 'https://example.test/cancel',
    ]);

    expect($session->id)->toStartWith('cs_')
        ->and($session->status)->toBe('pending')
        ->and($session->totalAmount)->toBe(49999);

    $fetched = $client->checkoutSessions->retrieve($session->id);

    expect($fetched->id)->toBe($session->id);
})->skip(fn () => ! e2eConfigured(), 'SHOTPAY_E2E_BASE_URL not set — run via shotpay-platform\'s sdk-e2e-php CI job');

test('retrieves a real contract seeded by the server', function () {
    $client = e2eClient();
    $reference = (string) getenv('SHOTPAY_E2E_CONTRACT_REFERENCE');

    $contract = $client->contracts->retrieve($reference);

    expect($contract->reference)->toBe($reference);
})->skip(fn () => ! e2eConfigured(), 'SHOTPAY_E2E_BASE_URL not set — run via shotpay-platform\'s sdk-e2e-php CI job');

test('verifies a signature the real server actually signed', function () {
    $verified = WebhookSignatureVerifier::verify(
        payload: (string) getenv('SHOTPAY_E2E_WEBHOOK_PAYLOAD'),
        secret: (string) getenv('SHOTPAY_E2E_WEBHOOK_SECRET'),
        header: (string) getenv('SHOTPAY_E2E_WEBHOOK_SIGNATURE'),
    );

    expect($verified)->toBeTrue();
})->skip(fn () => ! e2eConfigured(), 'SHOTPAY_E2E_BASE_URL not set — run via shotpay-platform\'s sdk-e2e-php CI job');
