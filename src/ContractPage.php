<?php

namespace ShotPay\Sdk;

/**
 * One page of `GET /v1/contracts`. Page size is fixed by the API and is not a
 * parameter — step through with the `page` param until `hasMorePages()` is
 * false.
 */
final class ContractPage
{
    public function __construct(
        /** @var array<int, Contract> */
        public readonly array $data,
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
        public readonly int $total,
        /**
         * Absolute URLs carrying the filter the page was fetched with. This
         * client builds requests from a base URL plus a relative path, so it
         * will not follow them for you.
         */
        public readonly ?string $nextPageUrl,
        public readonly ?string $previousPageUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $body  The whole envelope: `data`, `links`, `meta`.
     */
    public static function fromArray(array $body): self
    {
        $links = (array) ($body['links'] ?? []);
        $meta = (array) ($body['meta'] ?? []);

        return new self(
            data: array_map(
                static fn (array $contract): Contract => Contract::fromArray($contract),
                (array) ($body['data'] ?? []),
            ),
            currentPage: (int) ($meta['current_page'] ?? 1),
            lastPage: (int) ($meta['last_page'] ?? 1),
            perPage: (int) ($meta['per_page'] ?? 0),
            total: (int) ($meta['total'] ?? 0),
            nextPageUrl: isset($links['next']) ? (string) $links['next'] : null,
            previousPageUrl: isset($links['prev']) ? (string) $links['prev'] : null,
        );
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }
}
