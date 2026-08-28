<?php

namespace ShotPay\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use ShotPay\Sdk\Exceptions\ShotPayApiException;

/**
 * The HTTP layer the resource classes share. Deliberately returns decoded
 * arrays rather than models: the transport knows envelopes, the resources know
 * what to build from them.
 *
 * @internal
 */
final class Transport
{
    public function __construct(private readonly Client $http) {}

    /**
     * A `{"data": {…}}` envelope, unwrapped to the object inside.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function object(string $method, string $path, array $options = []): array
    {
        $body = $this->send($method, $path, $options);

        if (! isset($body['data']) || ! is_array($body['data'])) {
            throw $this->malformed('The ShotPay API returned a response without a `data` object.');
        }

        return $body['data'];
    }

    /**
     * A `{"data": […], "links": {…}, "meta": {…}}` envelope, returned whole.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function page(string $method, string $path, array $options = []): array
    {
        $body = $this->send($method, $path, $options);

        if (! isset($body['data']) || ! array_is_list($body['data'])) {
            throw $this->malformed('The ShotPay API returned a page without a `data` array.');
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $options): array
    {
        try {
            $response = $this->http->request($method, $path, $options);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();

            if ($response === null) {
                throw $exception;
            }

            $body = json_decode((string) $response->getBody(), true);

            throw new ShotPayApiException($response->getStatusCode(), is_array($body) ? $body : []);
        }

        $body = json_decode((string) $response->getBody(), true);

        return is_array($body) ? $body : [];
    }

    /**
     * Reuses the API exception rather than introducing a second class: a
     * caller already catches this one. The `200` reads oddly on purpose — the
     * request succeeded, the body did not say what it claimed to.
     */
    private function malformed(string $message): ShotPayApiException
    {
        return new ShotPayApiException(200, ['message' => $message]);
    }
}
