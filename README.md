# shotpay/php-sdk

A minimal PHP client for the ShotPay Checkout API. Covers the checkout
session lifecycle and webhook signature verification — nothing else. For
every other endpoint, call the widget API directly as documented at
`/docs/api`.

**Status: early access.** Not yet published to Packagist. Install from a
checkout of this repository or a git dependency in the meantime.

## Usage

```php
use ShotPay\Sdk\ShotPayClient;

$client = new ShotPayClient(
    clientId: 'mch_live_...',
    apiKey: 'sk_live_...',
);

$session = $client->createCheckoutSession([
    'order_reference' => 'ORD-1001',
    'total_amount' => 49999, // integer cents — $499.99
    'return_url' => 'https://store.example/checkout/complete',
    'cancel_url' => 'https://store.example/cart',
], idempotencyKey: 'a-uuid-per-checkout-attempt');

// Later, from your own server:
$session = $client->retrieveCheckoutSession($session->id);
```

## Verifying a webhook

```php
use ShotPay\Sdk\WebhookSignatureVerifier;

$header = $request->header('ShotPay-Signature');
$rawBody = $request->getContent(); // verify against the raw bytes, not re-serialized JSON

if (! WebhookSignatureVerifier::verify($rawBody, $endpointSecret, $header)) {
    abort(400);
}
```

`$endpointSecret` is shown once, when the endpoint is created or its secret
is rotated, in the merchant dashboard.
