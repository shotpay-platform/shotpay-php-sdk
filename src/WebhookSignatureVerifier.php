<?php

namespace ShotPay\Sdk;

/**
 * A standalone reimplementation of the platform's `App\Support\Webhooks\WebhookSignature::verify()`
 * (app/Support/Webhooks/WebhookSignature.php) — this package cannot depend on
 * the Laravel application, so the scheme is duplicated here. Keep the two in
 * sync: `t={unix},v1={hex}` over `hash_hmac('sha256', "{t}.{payload}", $secret)`.
 */
final class WebhookSignatureVerifier
{
    private const SCHEME = 'v1';

    /** The window published in the API docs for a merchant's own verifier to use. */
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    public static function verify(
        string $payload,
        string $secret,
        string $header,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
    ): bool {
        $parts = self::parse($header);
        $timestamp = $parts['t'] ?? null;
        $presented = $parts[self::SCHEME] ?? null;

        if ($timestamp === null || $presented === null) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > $toleranceSeconds) {
            return false;
        }

        return hash_equals(self::digest($payload, $secret, (int) $timestamp), $presented);
    }

    private static function digest(string $payload, string $secret, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
    }

    /**
     * @return array<string, string>
     */
    private static function parse(string $header): array
    {
        $parts = [];

        foreach (explode(',', $header) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, null);

            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }

        return $parts;
    }
}
