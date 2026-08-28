# shotpay/php

A PHP client for the ShotPay API. Covers the checkout session lifecycle, your
layaway contracts, and webhook signature verification. Every other endpoint
stays callable directly, as documented at `/docs/api`.

**Status: early access.** Not yet published to Packagist. Install from a
checkout of this repository or a git dependency in the meantime. Breaking
changes can land in a minor bump (`0.2.x` → `0.3.0`).

## Usage

```php
use ShotPay\Sdk\ShotPayClient;

$client = new ShotPayClient(
    clientId: 'mch_live_...',
    apiKey: 'sk_live_...',
);
```

### Checkout sessions

Needs a key with the `checkout:write` scope to open one, `checkout:read` to
read it back.

```php
$session = $client->checkoutSessions->create([
    'order_reference' => 'ORD-1001',
    'total_amount' => 49999, // integer cents — $499.99
    'return_url' => 'https://store.example/checkout/complete',
    'cancel_url' => 'https://store.example/cart',
], idempotencyKey: 'a-uuid-per-checkout-attempt');

// Later, from your own server:
$session = $client->checkoutSessions->retrieve($session->id);
```

### Contracts

The agreements a completed checkout becomes, addressed by the reference
webhooks carry. Reads need `contracts:read`; `update` and `cancel` need
`contracts:write`.

```php
$page = $client->contracts->list(['status' => 'active']);

foreach ($page->data as $contract) {
    echo $contract->reference, ' ', $contract->remainingAmount, PHP_EOL;
}

$contract = $client->contracts->retrieve('LAY-XK2M9QPR7V');
```

Page size is fixed by the API. Step through with `page`:

```php
$page = $client->contracts->list(['status' => 'active']);

while ($page->hasMorePages()) {
    $page = $client->contracts->list([
        'status' => 'active',
        'page' => $page->currentPage + 1,
    ]);
}
```

Only bookkeeping is writable — money, status and the customer's identity are
not. `metadata` **replaces** the stored map rather than merging into it; send
`null` to clear it.

```php
$contract = $client->contracts->update('LAY-XK2M9QPR7V', [
    'order_reference' => 'SO-2026-0042',
    'metadata' => ['sales_rep' => 'kim'],
]);
```

Cancelling stops further collection; installments already paid stay paid.
Cancelling an already-cancelled agreement returns it as it stands, so a retry
is safe — but one that already completed or defaulted raises a `422`.

```php
$client->contracts->cancel('LAY-XK2M9QPR7V');
```

### Deprecated

`$client->createCheckoutSession()` and `$client->retrieveCheckoutSession()`
still work and call the same endpoints, but are removed in `0.3.0`. Use
`$client->checkoutSessions->create()` and `->retrieve()`.

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
