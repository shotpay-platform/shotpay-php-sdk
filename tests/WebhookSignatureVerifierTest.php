<?php

use ShotPay\Sdk\WebhookSignatureVerifier;

function sdkSign(string $payload, string $secret, int $timestamp): string
{
    $digest = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    return "t={$timestamp},v1={$digest}";
}

beforeEach(function () {
    $this->secret = 'whsec_testing';
    $this->payload = '{"id":"evt_1","type":"contract.paid"}';
});

test('a signature verifies against the bytes it was made from', function () {
    $timestamp = time();
    $header = sdkSign($this->payload, $this->secret, $timestamp);

    expect(WebhookSignatureVerifier::verify($this->payload, $this->secret, $header))->toBeTrue();
});

test('a payload changed by one byte does not verify', function () {
    $header = sdkSign($this->payload, $this->secret, time());

    expect(WebhookSignatureVerifier::verify($this->payload.' ', $this->secret, $header))->toBeFalse();
});

test('another secret does not verify', function () {
    $header = sdkSign($this->payload, $this->secret, time());

    expect(WebhookSignatureVerifier::verify($this->payload, 'whsec_other', $header))->toBeFalse();
});

test('a delivery older than the tolerance does not verify', function () {
    $header = sdkSign($this->payload, $this->secret, time() - 600);

    expect(WebhookSignatureVerifier::verify($this->payload, $this->secret, $header, 300))->toBeFalse();
});

test('a malformed header does not verify', function (string $header) {
    expect(WebhookSignatureVerifier::verify($this->payload, $this->secret, $header))->toBeFalse();
})->with([
    'empty' => '',
    'no digest' => 't=1786512000',
    'no timestamp' => 'v1=abc',
    'nonsense' => 'garbage',
]);
