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
use function json_decode;
use function rawurlencode;

/**
 * Abstraction of useragentapi.com
 *
 * @see https://useragentapi.com/docs
 */
final class UserAgentApiCom extends AbstractHttpParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'UserAgentApiCom';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'http://useragentapi.com/';

    protected bool $local = false;

    protected bool $api = true;

    protected array $detectionCapabilities = [
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => true,
            'version' => true,
        ],

        'operatingSystem' => [
            'name' => false,
            'version' => false,
        ],

        'device' => [
            'model' => false,
            'brand' => false,
            'type' => true,
            'isMobile' => false,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => false,
        ],
    ];

    private static string $uri = 'https://useragentapi.com/api/v3/json';

    public function __construct(Client $client, private string $apiKey)
    {
        parent::__construct($client);
    }

    public function getVersion(): string | null
    {
        return null;
    }

    /**
     * @param array $headers
     *
     * @throws Exception\RequestException
     */
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
        if (true === $this->isBot($resultRaw)) {
            $this->hydrateBot($result->getBot(), $resultRaw);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw);
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $resultRaw);
        $this->hydrateDevice($result->getDevice(), $resultRaw);

        return $result;
    }

    /**
     * @throws Exception\RequestException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    protected function getResult(string $userAgent, array $headers): stdClass
    {
        /*
         * an empty UserAgent makes no sense
         */
        if ('' === $userAgent) {
            throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        $parameters  = '/' . $this->apiKey;
        $parameters .= '/' . rawurlencode($userAgent);

        $uri = self::$uri . $parameters;

        $request = new Request('GET', $uri);

        try {
            $response = $this->getResponse($request);
        } catch (Exception\RequestException $ex) {
            $prevEx = $ex->getPrevious();
            assert($prevEx instanceof ClientException);

            if (true === $prevEx->hasResponse() && 400 === $prevEx->getResponse()->getStatusCode()) {
                $content = $prevEx->getResponse()
                    ->getBody()
                    ->getContents();
                $content = json_decode($content);

                /*
                 * Error
                 */
                if (isset($content->error->code) && 'key_invalid' === $content->error->code) {
                    throw new Exception\InvalidCredentialsException('Your API key "' . $this->apiKey . '" is not valid for ' . $this->getName(), null, $ex);
                }

                if (isset($content->error->code) && 'useragent_invalid' === $content->error->code) {
                    throw new Exception\RequestException('User agent is invalid "' . $userAgent . '"');
                }
            }

            throw $ex;
        }

        /*
         * no json returned?
         */
        $contentType = $response->getHeader('Content-Type');
        if (!isset($contentType[0]) || 'application/json' !== $contentType[0]) {
            throw new Exception\RequestException('Could not get valid "application/json" response from "' . $request->getUri() . '". Response is "' . $response->getBody()->getContents() . '"');
        }

        $content = json_decode($response->getBody()->getContents());

        /*
         * No result
         */
        if (isset($content->error->code) && 'useragent_not_found' === $content->error->code) {
            throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Missing data?
         */
        if (!$content instanceof stdClass || !isset($content->data)) {
            throw new Exception\RequestException('Could not get valid response from "' . $request->getUri() . '". Data is missing "' . $response->getBody()->getContents() . '"');
        }

        return $content->data;
    }

    private function isBot(stdClass $resultRaw): bool
    {
        return isset($resultRaw->platform_type) && 'Bot' === $resultRaw->platform_type;
    }

    private function hydrateBot(Model\Bot $bot, stdClass $resultRaw): void
    {
        $bot->setIsBot(true);

        if (!isset($resultRaw->platform_name)) {
            return;
        }

        $bot->setName($this->getRealResult($resultRaw->platform_name));
    }

    private function hydrateBrowser(Model\Browser $browser, stdClass $resultRaw): void
    {
        if (isset($resultRaw->browser_name)) {
            $browser->setName($this->getRealResult($resultRaw->browser_name));
        }

        if (!isset($resultRaw->browser_version)) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->browser_version));
    }

    private function hydrateRenderingEngine(Model\RenderingEngine $engine, stdClass $resultRaw): void
    {
        if (isset($resultRaw->engine_name)) {
            $engine->setName($this->getRealResult($resultRaw->engine_name));
        }

        if (!isset($resultRaw->engine_version)) {
            return;
        }

        $engine->getVersion()->setComplete($this->getRealResult($resultRaw->engine_version));
    }

    private function hydrateDevice(Model\Device $device, stdClass $resultRaw): void
    {
        if (!isset($resultRaw->platform_type)) {
            return;
        }

        $device->setType($this->getRealResult($resultRaw->platform_type));
    }
}
