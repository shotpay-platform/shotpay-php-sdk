<?php

require __DIR__.'/vendor/autoload.php';

function check(bool $condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
}

foreach ([
    ShotPay\Sdk\ShotPayClient::class,
    ShotPay\Sdk\WebhookSignatureVerifier::class,
    ShotPay\Sdk\Exceptions\ShotPayApiException::class,
    ShotPay\Sdk\CheckoutSession::class,
    ShotPay\Sdk\Contract::class,
] as $class) {
    check(class_exists($class), "{$class} does not autoload from the installed package");
}

$package = __DIR__.'/vendor/shotpay/php';

check(! is_link($package), 'the package should be a real copy, not a symlink to the source tree');
check(! is_dir($package.'/consumer-e2e'), 'consumer-e2e should not ship in the package');
check(! is_dir($package.'/.github'), '.github should not ship in the package');
check(! is_dir($package.'/vendor'), 'a vendor directory should not ship in the package');

echo "autoload and package contents ok\n";
