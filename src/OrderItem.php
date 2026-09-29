<?php

namespace ShotPay\Sdk;

/**
 * One line of what the customer bought, as `OrderItemResource` returns it on
 * a `CheckoutSession` and a `Contract`, whichever platform described it.
 */
final class OrderItem
{
    public function __construct(
        public readonly string $name,
        /** Null when the product has none. */
        public readonly ?string $sku,
        public readonly int $quantity,
        /** The price of one unit, in integer minor units (cents). */
        public readonly int $unitAmount,
        /** Null when the product has no picture. */
        public readonly ?string $imageUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            sku: isset($data['sku']) ? (string) $data['sku'] : null,
            quantity: (int) $data['quantity'],
            unitAmount: (int) $data['unit_amount'],
            imageUrl: isset($data['image_url']) ? (string) $data['image_url'] : null,
        );
    }
}
