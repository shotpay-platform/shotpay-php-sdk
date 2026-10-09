<?php

// Live e2e through the *installed* package. Skipped without SHOTPAY_E2E_*,
// which shotpay-platform's sdk-e2e-php CI job exports after booting itself.

require __DIR__.'/vendor/autoload.php';

use ShotPay\Sdk\ShotPayClient;
use ShotPay\Sdk\WebhookSignatureVerifier;

function check(bool $condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
}

if ((string) getenv('SHOTPAY_E2E_BASE_URL') === '') {
    echo "SHOTPAY_E2E_BASE_URL not set — live e2e skipped\n";
    exit(0);
}

$client = new ShotPayClient(
    (string) getenv('SHOTPAY_E2E_CLIENT_ID'),
    (string) getenv('SHOTPAY_E2E_API_KEY'),
    (string) getenv('SHOTPAY_E2E_BASE_URL'),
);

$session = $client->checkoutSessions->create([
    'order_reference' => 'CONSUMER-'.bin2hex(random_bytes(4)),
    'total_amount' => 49999,
    'customer_state' => 'TX',
    'return_url' => 'https://example.test/return',
    'cancel_url' => 'https://example.test/cancel',
]);

check(str_starts_with($session->id, 'cs_'), 'a created session has a cs_ id');
check($client->checkoutSessions->retrieve($session->id)->id === $session->id, 'the session retrieves by id');

$reference = (string) getenv('SHOTPAY_E2E_CONTRACT_REFERENCE');

check($client->contracts->retrieve($reference)->reference === $reference, 'the seeded contract retrieves');

check(WebhookSignatureVerifier::verify(
    payload: (string) getenv('SHOTPAY_E2E_WEBHOOK_PAYLOAD'),
    secret: (string) getenv('SHOTPAY_E2E_WEBHOOK_SECRET'),
    header: (string) getenv('SHOTPAY_E2E_WEBHOOK_SIGNATURE'),
), 'the server-signed webhook verifies');

echo "live e2e through the installed package ok\n";
