<?php

namespace ShotPay\Sdk\Resources;

use ShotPay\Sdk\Contract;
use ShotPay\Sdk\ContractPage;
use ShotPay\Sdk\Transport;

/**
 * Your layaway agreements after checkout, addressed by the reference webhooks
 * carry. Reads need a key with the `contracts:read` scope; `update` and
 * `cancel` need `contracts:write`.
 */
final class Contracts
{
    public function __construct(private readonly Transport $transport) {}

    /**
     * `GET /v1/contracts`. Newest first, one page at a time. Page size is fixed
     * by the API and is not a parameter; step through with `page`, stopping
     * when `hasMorePages()` is false.
     *
     * @param  array{status?: string, page?: int}  $params
     */
    public function list(array $params = []): ContractPage
    {
        return ContractPage::fromArray(
            $this->transport->page('GET', 'v1/contracts', ['query' => $params]),
        );
    }

    /**
     * `GET /v1/contracts/{reference}`. One agreement with its full schedule.
     */
    public function retrieve(string $reference): Contract
    {
        return Contract::fromArray(
            $this->transport->object('GET', 'v1/contracts/'.rawurlencode($reference)),
        );
    }

    /**
     * `PATCH /v1/contracts/{reference}`. Corrects bookkeeping only — money,
     * status and the customer's identity are not writable. `metadata` replaces
     * the stored map rather than merging into it; send `null` to clear it.
     *
     * @param  array{order_reference?: string, metadata?: array<string, string>|null}  $params
     */
    public function update(string $reference, array $params): Contract
    {
        return Contract::fromArray(
            $this->transport->object('PATCH', 'v1/contracts/'.rawurlencode($reference), ['json' => $params]),
        );
    }

    /**
     * `POST /v1/contracts/{reference}/cancel`. No further installments are
     * collected; paid installments stay paid. Cancelling an already-cancelled
     * agreement answers with the agreement as it stands, so a retry is safe —
     * but one that already completed or defaulted raises a 422.
     */
    public function cancel(string $reference): Contract
    {
        return Contract::fromArray(
            $this->transport->object('POST', 'v1/contracts/'.rawurlencode($reference).'/cancel'),
        );
    }
}
