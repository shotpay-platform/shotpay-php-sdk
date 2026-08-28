<?php

namespace ShotPay\Sdk;

/**
 * A layaway agreement, mirroring the fields
 * `App\Http\Resources\V1\ContractResource` returns. Every amount is integer
 * minor units (cents). Identified by `reference` — the same value webhook
 * payloads carry — and deliberately carrying no row ids.
 */
final class Contract
{
    public function __construct(
        public readonly string $reference,
        public readonly ?string $orderReference,
        /** One of `active`, `completed`, `cancelled`, `defaulted`. */
        public readonly string $status,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly ?string $customerState,
        /** The order total before the fee. */
        public readonly int $totalAmount,
        public readonly int $serviceFeeAmount,
        /** The order total plus the fee — what the plan collects in all. */
        public readonly int $customerTotal,
        public readonly int $downPaymentAmount,
        public readonly int $paidAmount,
        public readonly int $remainingAmount,
        /** @var array<int, Installment> */
        public readonly array $installments,
        public readonly ?string $startedAt,
        public readonly ?string $completedAt,
        public readonly ?string $defaultedAt,
        public readonly ?string $cancelledAt,
        public readonly string $createdAt,
        /** @var array<string, string> Your own key/value pairs. */
        public readonly array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            reference: (string) $data['reference'],
            orderReference: isset($data['order_reference']) ? (string) $data['order_reference'] : null,
            status: (string) $data['status'],
            customerName: (string) $data['customer_name'],
            customerEmail: (string) $data['customer_email'],
            customerState: isset($data['customer_state']) ? (string) $data['customer_state'] : null,
            totalAmount: (int) $data['total_amount'],
            serviceFeeAmount: (int) $data['service_fee_amount'],
            customerTotal: (int) $data['customer_total'],
            downPaymentAmount: (int) $data['down_payment_amount'],
            paidAmount: (int) $data['paid_amount'],
            remainingAmount: (int) $data['remaining_amount'],
            installments: array_map(
                static fn (array $installment): Installment => Installment::fromArray($installment),
                (array) ($data['installments'] ?? []),
            ),
            startedAt: isset($data['started_at']) ? (string) $data['started_at'] : null,
            completedAt: isset($data['completed_at']) ? (string) $data['completed_at'] : null,
            defaultedAt: isset($data['defaulted_at']) ? (string) $data['defaulted_at'] : null,
            cancelledAt: isset($data['cancelled_at']) ? (string) $data['cancelled_at'] : null,
            createdAt: (string) $data['created_at'],
            // Defaulted for a client pinned to a build of this package that
            // predates the field meeting a payload that carries it.
            metadata: array_map('strval', (array) ($data['metadata'] ?? [])),
        );
    }
}
