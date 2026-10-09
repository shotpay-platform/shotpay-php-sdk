#!/usr/bin/env bash
# Installs this package into a throwaway consumer project the way a real
# consumer gets it — from a `git archive` zip of the committed tree, which is
# what GitHub and Packagist serve, so .gitattributes export-ignore, autoload and
# the shipped file list are what is under test, not the working tree — then
# checks the autoloader and, when SHOTPAY_E2E_* vars are set, runs a live e2e.
set -euo pipefail

repo="$(cd "$(dirname "$0")/.." && pwd)"
consumer="$repo/consumer-e2e"

rm -rf "$consumer/artifacts" "$consumer/vendor" "$consumer/composer.lock"
mkdir -p "$consumer/artifacts"

git -C "$repo" archive --format=zip --output="$consumer/artifacts/shotpay-php.zip" HEAD

cd "$consumer"
composer update --no-interaction --prefer-dist --no-progress

php check.php
php e2e.php
