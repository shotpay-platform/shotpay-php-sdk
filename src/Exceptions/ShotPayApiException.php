<?php

namespace ShotPay\Sdk\Exceptions;

use RuntimeException;

class ShotPayApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(public readonly int $status, public readonly array $body)
    {
        parent::__construct((string) ($body['message'] ?? "The ShotPay API responded with status {$status}."));
    }
}
