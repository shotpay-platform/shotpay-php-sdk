<?php

namespace ShotPay\Sdk;

/**
 * Mirrors the fields `CheckoutSessionResource` returns from both
 * `POST /v1/sessions` and `GET /v1/checkout-sessions/{id}`.
 */
final class CheckoutSession
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly string $orderReference,
        /** Integer minor units (cents) — 42900 is $429.00. */
        public readonly int $totalAmount,
        public readonly int $downPaymentAmount,
        public readonly int $downPaymentPercentage,
        public readonly string $checkoutUrl,
        public readonly string $returnUrl,
        public readonly string $cancelUrl,
        public readonly string $expiresAt,
        public readonly string $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            status: (string) $data['status'],
            orderReference: (string) $data['order_reference'],
            totalAmount: (int) $data['total_amount'],
            downPaymentAmount: (int) $data['down_payment_amount'],
            downPaymentPercentage: (int) $data['down_payment_percentage'],
            checkoutUrl: (string) $data['checkout_url'],
            returnUrl: (string) $data['return_url'],
            cancelUrl: (string) $data['cancel_url'],
            expiresAt: (string) $data['expires_at'],
            createdAt: (string) $data['created_at'],
        );
    }
}
