<?php

namespace ShotPay\Sdk;

/**
 * One scheduled payment on a `Contract`, as `ContractResource` returns it.
 */
final class Installment
{
    public function __construct(
        public readonly int $sequence,
        /** Integer minor units (cents) — 42900 is $429.00. */
        public readonly int $amount,
        /** `YYYY-MM-DD` — a calendar date, not a timestamp. */
        public readonly string $dueDate,
        public readonly string $status,
        public readonly ?string $paidAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sequence: (int) $data['sequence'],
            amount: (int) $data['amount'],
            dueDate: (string) $data['due_date'],
            status: (string) $data['status'],
            paidAt: isset($data['paid_at']) ? (string) $data['paid_at'] : null,
        );
    }
}
