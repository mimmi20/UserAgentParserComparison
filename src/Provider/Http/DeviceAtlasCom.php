<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use stdClass;
use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Model;

use function array_change_key_case;
use function assert;
use function count;
use function json_decode;
use function rawurlencode;

/**
 * Abstraction of deviceatlas.com
 *
 * @see https://deviceatlas.com/resources/enterprise-api-documentation
 */
final class DeviceAtlasCom extends AbstractHttpParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'DeviceAtlasCom';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://deviceatlas.com/';

    protected bool $local = false;

    protected bool $api = true;

    protected array $detectionCapabilities = [
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => true,
            'version' => false,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'model' => false,
            'brand' => false,
            'type' => true,
            'isMobile' => false,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => false,
            'name' => false,
            'type' => false,
        ],
    ];

    private static string $uri = 'http://region0.deviceatlascloud.com/v1/detect/properties';

    public function __construct(Client $client, private string $apiKey)
    {
        parent::__construct($client);
    }

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $resultRaw = $this->getResult($userAgent, $headers);

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($resultRaw);

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw);
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $resultRaw);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw);
        $this->hydrateDevice($result->getDevice(), $resultRaw);

        return $result;
    }

    protected function getResult(string $userAgent, array $headers): stdClass
    {
        /*
         * an empty UserAgent makes no sense
         */
        if ('' === $userAgent) {
            throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        $parameters  = '?licencekey=' . rawurlencode($this->apiKey);
        $parameters .= '&useragent=' . rawurlencode($userAgent);

        $uri = self::$uri . $parameters;

        // key to lower
        $headers = array_change_key_case($headers);

        $newHeaders = [];
        foreach ($headers as $key => $value) {
            $newHeaders['X-DA-' . $key] = $value;
        }

        $newHeaders['User-Agent'] = 'ThaDafinser/UserAgentParserComparison:v1.4';

        $request = new Request('GET', $uri, $newHeaders);

        try {
            $response = $this->getResponse($request);
        } catch (Exception\RequestException $ex) {
            $prevEx = $ex->getPrevious();
            assert($prevEx instanceof ClientException);

            if (true === $prevEx->hasResponse() && 403 === $prevEx->getResponse()->getStatusCode()) {
                throw new Exception\InvalidCredentialsException('Your API key "' . $this->apiKey . '" is not valid for ' . $this->getName(), null, $ex);
            }

            throw $ex;
        }

        /*
         * no json returned?
         */
        $contentType = $response->getHeader('Content-Type');
        if (!isset($contentType[0]) || 'application/json; charset=UTF-8' !== $contentType[0]) {
            throw new Exception\RequestException('Could not get valid "application/json" response from "' . $request->getUri() . '". Response is "' . $response->getBody()->getContents() . '"');
        }

        $content = json_decode($response->getBody()->getContents());

        if (!$content instanceof stdClass || !isset($content->properties)) {
            throw new Exception\RequestException('Could not get valid response from "' . $request->getUri() . '". Response is "' . $response->getBody()->getContents() . '"');
        }

        /*
         * No result found?
         */
        if (!$content->properties instanceof stdClass || 0 === count((array) $content->properties)) {
            throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        return $content->properties;
    }

    private function hydrateBrowser(Model\Browser $browser, stdClass $resultRaw): void
    {
        if (isset($resultRaw->browserName)) {
            $browser->setName($this->getRealResult($resultRaw->browserName, 'browser', 'name'));
        }

        if (!isset($resultRaw->browserVersion)) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->browserVersion, 'browser', 'version'));
    }

    private function hydrateRenderingEngine(Model\RenderingEngine $engine, stdClass $resultRaw): void
    {
        if (!isset($resultRaw->browserRenderingEngine)) {
            return;
        }

        $engine->setName($this->getRealResult($resultRaw->browserRenderingEngine));
    }

    private function hydrateOperatingSystem(Model\OperatingSystem $os, stdClass $resultRaw): void
    {
        if (isset($resultRaw->osName)) {
            $os->setName($this->getRealResult($resultRaw->osName));
        }

        if (!isset($resultRaw->osVersion)) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($resultRaw->osVersion));
    }

    /** @param Model\UserAgent $device */
    private function hydrateDevice(Model\Device $device, stdClass $resultRaw): void
    {
        if (!isset($resultRaw->primaryHardwareType)) {
            return;
        }

        $device->setType($this->getRealResult($resultRaw->primaryHardwareType));
    }
}
