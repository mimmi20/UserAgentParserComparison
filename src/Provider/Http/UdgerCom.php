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

/**
 * Abstraction of udger.com
 *
 * @see https://udger.com/support/documentation/?doc=38
 */
final class UdgerCom extends AbstractHttpParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'UdgerCom';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://udger.com/';

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
            'name' => false,
            'type' => false,
        ],
    ];

    protected array $defaultValues = [
        'general' => ['/^unknown$/i'],
    ];

    private static string $uri = 'http://api.udger.com/parse';

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
        if (true === $this->isBot($resultRaw->info)) {
            $this->hydrateBot($result->getBot(), $resultRaw->info);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw->info);
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $resultRaw->info);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw->info);
        $this->hydrateDevice($result->getDevice(), $resultRaw->info);

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
            'accesskey' => $this->apiKey,
            'uastrig' => $userAgent,
        ];

        $body = http_build_query($params, null, '&');

        $request = new Request('POST', self::$uri, ['Content-Type' => 'application/x-www-form-urlencoded'], $body);

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
         * No result found?
         */
        if (isset($content->flag) && 3 === $content->flag) {
            throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Errors
         */
        if (isset($content->flag) && 4 === $content->flag) {
            throw new Exception\InvalidCredentialsException('Your API key "' . $this->apiKey . '" is not valid for ' . $this->getName());
        }

        if (isset($content->flag) && 6 === $content->flag) {
            throw new Exception\LimitationExceededException('Exceeded the maximum number of request with API key "' . $this->apiKey . '" for ' . $this->getName());
        }

        if (isset($content->flag) && 3 < $content->flag) {
            throw new Exception\RequestException('Could not get valid response from "' . $request->getUri() . '". Response is "' . $response->getBody()->getContents() . '"');
        }

        /*
         * Missing data?
         */
        if (!$content instanceof stdClass || !isset($content->info)) {
            throw new Exception\RequestException('Could not get valid response from "' . $request->getUri() . '". Response is "' . $response->getBody()->getContents() . '"');
        }

        return $content;
    }

    private function isBot(stdClass $resultRaw): bool
    {
        return isset($resultRaw->type) && 'Robot' === $resultRaw->type;
    }

    private function hydrateBot(Model\Bot $bot, stdClass $resultRaw): void
    {
        $bot->setIsBot(true);

        if (!isset($resultRaw->ua_family)) {
            return;
        }

        $bot->setName($this->getRealResult($resultRaw->ua_family));
    }

    private function hydrateBrowser(Model\Browser $browser, stdClass $resultRaw): void
    {
        if (isset($resultRaw->ua_family)) {
            $browser->setName($this->getRealResult($resultRaw->ua_family, 'browser', 'name'));
        }

        if (!isset($resultRaw->ua_ver)) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->ua_ver));
    }

    private function hydrateRenderingEngine(Model\RenderingEngine $engine, stdClass $resultRaw): void
    {
        if (!isset($resultRaw->ua_engine)) {
            return;
        }

        $engine->setName($this->getRealResult($resultRaw->ua_engine));
    }

    private function hydrateOperatingSystem(Model\OperatingSystem $os, stdClass $resultRaw): void
    {
        if (!isset($resultRaw->os_family)) {
            return;
        }

        $os->setName($this->getRealResult($resultRaw->os_family));
    }

    private function hydrateDevice(Model\Device $device, stdClass $resultRaw): void
    {
        if (!isset($resultRaw->device_name)) {
            return;
        }

        $device->setType($this->getRealResult($resultRaw->device_name));
    }
}
