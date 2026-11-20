<?php

namespace App\Services\Claude;

use Illuminate\Support\Facades\Http;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Sends the Anthropic SDK's requests through Laravel's HTTP client, so they share the app's HTTP stack and tests can
 * fake them with Http::fake() (no real API call in a test).
 */
class HttpTransporter implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            if (strcasecmp($name, 'Host') !== 0 && strcasecmp($name, 'Content-Length') !== 0) {
                $headers[$name] = implode(', ', $values);
            }
        }

        $pending = Http::withHeaders($headers)->timeout(180);
        $body = (string) $request->getBody();
        if ($body !== '') {
            $pending = $pending->withBody($body, $request->getHeaderLine('Content-Type') ?: 'application/json');
        }

        return $pending->send($request->getMethod(), (string) $request->getUri())->toPsrResponse();
    }
}
