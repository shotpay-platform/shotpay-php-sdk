<?php

use GuzzleHttp\Psr7\Response;
use ShotPay\Sdk\Contract;
use ShotPay\Sdk\Exceptions\ShotPayApiException;

test('list sends the filter as a query string and maps the page', function () {
    $history = [];
    $client = sdkClient([jsonResponse([
        'data' => [contractPayload()],
        'links' => ['first' => 'https://x/?page=1', 'last' => 'https://x/?page=3', 'prev' => null, 'next' => 'https://x/?page=2'],
        'meta' => ['current_page' => 1, 'last_page' => 3, 'per_page' => 15, 'total' => 42, 'from' => 1, 'to' => 15],
    ])], $history);

    $page = $client->contracts->list(['status' => 'active', 'page' => 1]);

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('GET')
        ->and($request->getUri()->getPath())->toBe('/api/v1/contracts')
        ->and($request->getUri()->getQuery())->toBe('status=active&page=1')
        ->and($page->data)->toHaveCount(1)
        ->and($page->data[0])->toBeInstanceOf(Contract::class)
        ->and($page->currentPage)->toBe(1)
        ->and($page->lastPage)->toBe(3)
        ->and($page->perPage)->toBe(15)
        ->and($page->total)->toBe(42)
        ->and($page->nextPageUrl)->toBe('https://x/?page=2')
        ->and($page->previousPageUrl)->toBeNull()
        ->and($page->hasMorePages())->toBeTrue();
});

test('list with no parameters sends no query string', function () {
    $history = [];
    $client = sdkClient([jsonResponse([
        'data' => [],
        'links' => [],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0],
    ])], $history);

    $page = $client->contracts->list();

    expect($history[0]['request']->getUri()->getQuery())->toBe('')
        ->and($page->data)->toBe([])
        ->and($page->hasMorePages())->toBeFalse();
});

test('every request carries the credential headers and no content type without a body', function () {
    $history = [];
    $client = sdkClient([jsonResponse(['data' => contractPayload()])], $history);

    $client->contracts->retrieve('LAY-XK2M9QPR7V');

    $request = $history[0]['request'];
    expect($request->getHeaderLine('X-Client-Id'))->toBe('mch_test_1')
        ->and($request->getHeaderLine('X-Api-Key'))->toBe('sk_test_1')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($request->hasHeader('Content-Type'))->toBeFalse();
});

test('retrieve maps every field including the schedule', function () {
    $client = sdkClient([jsonResponse(['data' => contractPayload()])]);

    $contract = $client->contracts->retrieve('LAY-XK2M9QPR7V');

    expect($contract->reference)->toBe('LAY-XK2M9QPR7V')
        ->and($contract->orderReference)->toBe('SO-2026-0042')
        ->and($contract->status)->toBe('active')
        ->and($contract->customerName)->toBe('Dana Reyes')
        ->and($contract->customerEmail)->toBe('dana@example.com')
        ->and($contract->customerState)->toBe('CA')
        ->and($contract->totalAmount)->toBe(100000)
        ->and($contract->serviceFeeAmount)->toBe(5000)
        ->and($contract->customerTotal)->toBe(105000)
        ->and($contract->downPaymentAmount)->toBe(26250)
        ->and($contract->paidAmount)->toBe(26250)
        ->and($contract->remainingAmount)->toBe(78750)
        ->and($contract->metadata)->toBe(['sales_rep' => 'kim'])
        ->and($contract->installments)->toHaveCount(2)
        ->and($contract->installments[0]->sequence)->toBe(1)
        ->and($contract->installments[0]->amount)->toBe(26250)
        ->and($contract->installments[0]->dueDate)->toBe('2026-09-25')
        ->and($contract->installments[0]->status)->toBe('paid')
        ->and($contract->installments[0]->paidAt)->toBe('2026-09-25T10:00:00.000000Z')
        ->and($contract->installments[1]->paidAt)->toBeNull();
});

test('the nullable fields survive arriving null', function () {
    $client = sdkClient([jsonResponse(['data' => contractPayload([
        'order_reference' => null,
        'customer_state' => null,
        'started_at' => null,
        'completed_at' => null,
        'defaulted_at' => null,
        'cancelled_at' => null,
        'metadata' => [],
    ])])]);

    $contract = $client->contracts->retrieve('LAY-XK2M9QPR7V');

    expect($contract->orderReference)->toBeNull()
        ->and($contract->customerState)->toBeNull()
        ->and($contract->startedAt)->toBeNull()
        ->and($contract->completedAt)->toBeNull()
        ->and($contract->defaultedAt)->toBeNull()
        ->and($contract->cancelledAt)->toBeNull()
        ->and($contract->metadata)->toBe([]);
});

test('a reference needing escaping is encoded into the path', function () {
    $history = [];
    $client = sdkClient([jsonResponse(['data' => contractPayload()])], $history);

    $client->contracts->retrieve('LAY/../secrets');

    expect($history[0]['request']->getUri()->getPath())->toBe('/api/v1/contracts/LAY%2F..%2Fsecrets');
});

test('update sends a patch carrying the body as passed', function () {
    $history = [];
    $client = sdkClient([jsonResponse(['data' => contractPayload(['order_reference' => 'SO-9'])])], $history);

    $contract = $client->contracts->update('LAY-XK2M9QPR7V', ['order_reference' => 'SO-9']);

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('PATCH')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and((string) $request->getBody())->toBe('{"order_reference":"SO-9"}')
        ->and($contract->orderReference)->toBe('SO-9');
});

test('update clearing metadata sends json null rather than dropping the key', function () {
    $history = [];
    $client = sdkClient([jsonResponse(['data' => contractPayload(['metadata' => []])])], $history);

    $client->contracts->update('LAY-XK2M9QPR7V', ['metadata' => null]);

    expect((string) $history[0]['request']->getBody())->toBe('{"metadata":null}');
});

test('cancel posts to the cancel path with no body', function () {
    $history = [];
    $client = sdkClient([jsonResponse(['data' => contractPayload([
        'status' => 'cancelled',
        'cancelled_at' => '2026-08-26T12:00:00.000000Z',
    ])])], $history);

    $contract = $client->contracts->cancel('LAY-XK2M9QPR7V');

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('POST')
        ->and($request->getUri()->getPath())->toBe('/api/v1/contracts/LAY-XK2M9QPR7V/cancel')
        ->and((string) $request->getBody())->toBe('')
        ->and($request->hasHeader('Content-Type'))->toBeFalse()
        ->and($contract->status)->toBe('cancelled')
        ->and($contract->cancelledAt)->toBe('2026-08-26T12:00:00.000000Z');
});

test('an unknown reference raises the api exception carrying the status and body', function () {
    $client = sdkClient([jsonResponse(['message' => 'No contract matches that reference.'], 404)]);

    expect(fn () => $client->contracts->retrieve('LAY-NOPE'))
        ->toThrow(function (ShotPayApiException $exception) {
            expect($exception->status)->toBe(404)
                ->and($exception->getMessage())->toBe('No contract matches that reference.');
        });
});

test('a key missing the write scope raises the api exception', function () {
    $client = sdkClient([jsonResponse(['message' => 'This key is missing the contracts:write scope.'], 403)]);

    expect(fn () => $client->contracts->cancel('LAY-XK2M9QPR7V'))
        ->toThrow(function (ShotPayApiException $exception) {
            expect($exception->status)->toBe(403);
        });
});

test('cancelling a resolved agreement raises with the body preserved', function () {
    $client = sdkClient([jsonResponse(['message' => 'This agreement has already completed.'], 422)]);

    expect(fn () => $client->contracts->cancel('LAY-XK2M9QPR7V'))
        ->toThrow(function (ShotPayApiException $exception) {
            expect($exception->status)->toBe(422)
                ->and($exception->body)->toBe(['message' => 'This agreement has already completed.']);
        });
});

test('a throttled response with an empty body still raises the api exception', function () {
    $client = sdkClient([new Response(429)]);

    expect(fn () => $client->contracts->list())
        ->toThrow(function (ShotPayApiException $exception) {
            expect($exception->status)->toBe(429)
                ->and($exception->body)->toBe([]);
        });
});

test('a truncated success body raises rather than returning a half-built contract', function () {
    $client = sdkClient([new Response(200, ['Content-Type' => 'application/json'], '{"da')]);

    expect(fn () => $client->contracts->retrieve('LAY-XK2M9QPR7V'))
        ->toThrow(ShotPayApiException::class, 'The ShotPay API returned a response without a `data` object.');
});
