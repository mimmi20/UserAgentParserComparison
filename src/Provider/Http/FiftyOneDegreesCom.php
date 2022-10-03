<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use stdClass;
use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Model;

use function assert;
use function count;
use function implode;
use function is_array;
use function json_decode;
use function rawurlencode;

/**
 * Abstraction of neutrinoapi.com
 *
 * @see https://51degrees.com
 */
final class FiftyOneDegreesCom extends AbstractHttpParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'FiftyOneDegreesCom';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://51degrees.com';

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
            'model' => true,
            'brand' => true,
            'type' => true,
            'isMobile' => true,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => false,
            'type' => false,
        ],
    ];

    protected array $defaultValues = [
        'general' => ['/^Unknown$/i'],
    ];

    private static string $uri = 'https://cloud.51degrees.com/api/v1';

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
         * Bot detection
         */
        if (isset($resultRaw->IsCrawler) && true === $resultRaw->IsCrawler) {
            $this->hydrateBot($result->getBot(), $resultRaw);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw);
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $resultRaw);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw);
        $this->hydrateDevice($result->getDevice(), $resultRaw);

        return $result;
    }

    /** @throws Exception\RequestException */
    protected function getResult(string $userAgent, array $headers): stdClass
    {
        /*
         * an empty UserAgent makes no sense
         */
        if ('' === $userAgent) {
            throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        $headers['User-Agent'] = $userAgent;

        $parameters  = '/' . $this->apiKey;
        $parameters .= '/match?';

        $headerString = [];
        foreach ($headers as $key => $value) {
            $headerString[] = $key . '=' . rawurlencode($value);
        }

        $parameters .= implode('&', $headerString);

        $uri = self::$uri . $parameters;

        $request = new Request('GET', $uri);

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
        if (!isset($contentType[0]) || 'application/json; charset=utf-8' !== $contentType[0]) {
            throw new Exception\RequestException('Could not get valid "application/json; charset=utf-8" response from "' . $request->getUri() . '". Response is "' . $response->getBody()->getContents() . '"');
        }

        $content = json_decode($response->getBody()->getContents());

        /*
         * No result
         */
        if (isset($content->MatchMethod) && 'None' === $content->MatchMethod) {
            throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Missing data?
         */
        if (!$content instanceof stdClass || !isset($content->Values)) {
            throw new Exception\RequestException('Could not get valid response from "' . $request->getUri() . '". Data is missing "' . $response->getBody()->getContents() . '"');
        }

        /*
         * Convert the values, to something useable
         */
        $values              = new stdClass();
        $values->MatchMethod = $content->MatchMethod;

        foreach ($content->Values as $key => $value) {
            if (!is_array($value) || 1 !== count($value) || !isset($value[0])) {
                continue;
            }

            $values->{$key} = $value[0];
        }

        foreach ($values as $key => $value) {
            if ('True' === $value) {
                $values->{$key} = true;
            } elseif ('False' === $value) {
                $values->{$key} = false;
            }
        }

        return $values;
    }

    /** @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter */
    private function hydrateBot(Model\Bot $bot, stdClass $resultRaw): void
    {
        $bot->setIsBot(true);
    }

    private function hydrateBrowser(Model\Browser $browser, stdClass $resultRaw): void
    {
        if (isset($resultRaw->BrowserName)) {
            $browser->setName($this->getRealResult($resultRaw->BrowserName));
        }

        if (!isset($resultRaw->BrowserVersion)) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->BrowserVersion));
    }

    private function hydrateRenderingEngine(Model\RenderingEngine $engine, stdClass $resultRaw): void
    {
        if (!isset($resultRaw->LayoutEngine)) {
            return;
        }

        $engine->setName($this->getRealResult($resultRaw->LayoutEngine));
    }

    private function hydrateOperatingSystem(Model\OperatingSystem $os, stdClass $resultRaw): void
    {
        if (isset($resultRaw->PlatformName)) {
            $os->setName($this->getRealResult($resultRaw->PlatformName));
        }

        if (!isset($resultRaw->PlatformVersion)) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($resultRaw->PlatformVersion));
    }

    private function hydrateDevice(Model\Device $device, stdClass $resultRaw): void
    {
        if (isset($resultRaw->HardwareVendor)) {
            $device->setBrand($this->getRealResult($resultRaw->HardwareVendor));
        }

        if (isset($resultRaw->HardwareFamily)) {
            $device->setModel($this->getRealResult($resultRaw->HardwareFamily));
        }

        if (isset($resultRaw->DeviceType)) {
            $device->setType($this->getRealResult($resultRaw->DeviceType));
        }

        if (!isset($resultRaw->IsMobile)) {
            return;
        }

        $device->setIsMobile($this->getRealResult($resultRaw->IsMobile));
    }
}
