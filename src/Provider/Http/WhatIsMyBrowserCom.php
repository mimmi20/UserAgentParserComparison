<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use stdClass;
use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Model;

use function http_build_query;
use function json_decode;
use function print_r;

/**
 * Abstraction of useragentstring.com
 *
 * @see https://developers.whatismybrowser.com/reference
 */
final class WhatIsMyBrowserCom extends AbstractHttpParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'WhatIsMyBrowserCom';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://www.whatismybrowser.com/';

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
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'model' => true,
            'brand' => true,

            'type' => true,
            'isMobile' => false,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => true,
        ],
    ];

    protected array $defaultValues = [
        'general' => [],

        'browser' => [
            'name' => [
                '/^Unknown Mobile Browser$/i',
                '/^Unknown browser$/i',
                '/^Webkit based browser$/i',
                '/^a UNIX based OS$/i',
            ],
        ],

        'operatingSystem' => [
            'name' => ['/^Smart TV$/i'],
        ],

        'device' => [
            'model' => [
                // HTC generic or large parser error (over 1000 found)
                '/^HTC$/i',
                '/^Mobile$/i',
                '/^Android Phone$/i',
                '/^Android Tablet$/i',
                '/^Tablet$/i',
            ],
        ],
    ];

    private static string $uri = 'http://api.whatismybrowser.com/api/v1/user_agent_parse';

    public function __construct(Client $client, private string $apiKey)
    {
        parent::__construct($client);
    }

    public function getVersion(): string | null
    {
        return null;
    }

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $resultRaw = $this->getResult($userAgent, $headers);

        /*
         * No result found?
         */
        if (true !== $this->hasResult($resultRaw)) {
            throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

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
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw);
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

        $params = [
            'user_key' => $this->apiKey,
            'user_agent' => $userAgent,
        ];

        $body = http_build_query($params, null, '&');

        $request = new Request('POST', self::$uri, [], $body);

        $response = $this->getResponse($request);

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
        if (isset($content->message_code) && 'no_user_agent' === $content->message_code) {
            throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Limit exceeded
         */
        if (isset($content->message_code) && 'usage_limit_exceeded' === $content->message_code) {
            throw new Exception\LimitationExceededException('Exceeded the maximum number of request with API key "' . $this->apiKey . '" for ' . $this->getName());
        }

        /*
         * Error
         */
        if (isset($content->message_code) && 'no_api_user_key' === $content->message_code) {
            throw new Exception\InvalidCredentialsException('Missing API key for ' . $this->getName());
        }

        if (isset($content->message_code) && 'user_key_invalid' === $content->message_code) {
            throw new Exception\InvalidCredentialsException('Your API key "' . $this->apiKey . '" is not valid for ' . $this->getName());
        }

        if (!isset($content->result) || 'success' !== $content->result) {
            throw new Exception\RequestException('Could not get valid response from "' . $request->getUri() . '". Response is "' . $response->getBody()->getContents() . '"');
        }

        /*
         * Missing data?
         */
        if (!$content instanceof stdClass || !isset($content->parse) || !$content->parse instanceof stdClass) {
            throw new Exception\RequestException('Could not get valid response from "' . $request->getUri() . '". Response is "' . print_r($content, true) . '"');
        }

        return $content->parse;
    }

    private function hasResult(stdClass $resultRaw): bool
    {
        if (isset($resultRaw->browser_name) && true === $this->isRealResult($resultRaw->browser_name, 'browser', 'name')) {
            return true;
        }

        if (isset($resultRaw->layout_engine_name) && true === $this->isRealResult($resultRaw->layout_engine_name)) {
            return true;
        }

        if (isset($resultRaw->operating_system_name) && true === $this->isRealResult($resultRaw->operating_system_name, 'operatingSystem', 'name')) {
            return true;
        }

        if (isset($resultRaw->operating_platform) && true === $this->isRealResult($resultRaw->operating_platform, 'device', 'model')) {
            return true;
        }

        return isset($resultRaw->operating_platform_vendor_name) && true === $this->isRealResult($resultRaw->operating_platform_vendor_name);
    }

    private function isBot(stdClass $resultRaw): bool
    {
        return isset($resultRaw->software_type) && 'bot' === $resultRaw->software_type;
    }

    private function hydrateBot(Model\Bot $bot, stdClass $resultRaw): void
    {
        $bot->setIsBot(true);

        if (isset($resultRaw->browser_name)) {
            $bot->setName($this->getRealResult($resultRaw->browser_name));
        }

        if (!isset($resultRaw->software_sub_type)) {
            return;
        }

        $bot->setType($this->getRealResult($resultRaw->software_sub_type));
    }

    private function hydrateBrowser(Model\Browser $browser, stdClass $resultRaw): void
    {
        if (isset($resultRaw->browser_name)) {
            $browser->setName($this->getRealResult($resultRaw->browser_name, 'browser', 'name'));
        }

        if (!isset($resultRaw->browser_version_full)) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->browser_version_full));
    }

    private function hydrateRenderingEngine(Model\RenderingEngine $engine, stdClass $resultRaw): void
    {
        if (isset($resultRaw->layout_engine_name)) {
            $engine->setName($this->getRealResult($resultRaw->layout_engine_name));
        }

        if (!isset($resultRaw->layout_engine_version)) {
            return;
        }

        $engine->getVersion()->setComplete($this->getRealResult($resultRaw->layout_engine_version));
    }

    private function hydrateOperatingSystem(Model\OperatingSystem $os, stdClass $resultRaw): void
    {
        if (isset($resultRaw->operating_system_name)) {
            $os->setName($this->getRealResult($resultRaw->operating_system_name, 'operatingSystem', 'name'));
        }

        if (!isset($resultRaw->operating_system_version_full)) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($resultRaw->operating_system_version_full));
    }

    private function hydrateDevice(Model\Device $device, stdClass $resultRaw): void
    {
        if (isset($resultRaw->operating_platform)) {
            $device->setModel($this->getRealResult($resultRaw->operating_platform, 'device', 'model'));
        }

        if (isset($resultRaw->operating_platform_vendor_name)) {
            $device->setBrand($this->getRealResult($resultRaw->operating_platform_vendor_name));
        }

        if (!isset($resultRaw->hardware_type)) {
            return;
        }

        $device->setType($this->getRealResult($resultRaw->hardware_type));
    }
}
