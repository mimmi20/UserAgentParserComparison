<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException as GuzzleHttpException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Provider\AbstractParseProvider;

use function assert;

/**
 * Abstraction for all HTTP providers
 */
abstract class AbstractHttpParseProvider extends AbstractParseProvider
{
    public function __construct(private Client $client)
    {
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /** @throws Exception\RequestException */
    protected function getResponse(RequestInterface $request): Response
    {
        try {
            $response = $this->getClient()->send($request);
            assert($response instanceof Response);
        } catch (GuzzleHttpException $ex) {
            throw new Exception\RequestException('Could not get valid response from "' . $request->getUri() . '"', null, $ex);
        }

        if (200 !== $response->getStatusCode()) {
            throw new Exception\RequestException('Could not get valid response from "' . $request->getUri() . '". Status code is: "' . $response->getStatusCode() . '"');
        }

        return $response;
    }
}
