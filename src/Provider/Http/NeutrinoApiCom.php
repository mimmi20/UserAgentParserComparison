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
use function http_build_query;
use function json_decode;
use function print_r;

/**
 * Abstraction of neutrinoapi.com
 *
 * @see https://www.neutrinoapi.com/api/user-agent-info/
 */
final class NeutrinoApiCom extends AbstractHttpParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'NeutrinoApiCom';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://www.neutrinoapi.com/';

    protected bool $local = false;

    protected bool $api = true;

    protected array $detectionCapabilities = [
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => false,
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
            'name' => true,
            'type' => false,
        ],
    ];

    protected array $defaultValues = [
        'general' => ['/^unknown$/i'],

        'device' => [
            'brand' => [
                '/^Generic$/i',
                '/^generic web browser$/i',
            ],

            'model' => [
                '/^Android/i',
                '/^Windows Phone/i',
                '/^Windows Mobile/i',
                '/^Firefox/i',
                '/^Generic/i',
                '/^Tablet on Android$/i',
                '/^Tablet$/i',
            ],
        ],
    ];

    private static string $uri = 'https://neutrinoapi.com/user-agent-info';

    public function __construct(Client $client, private string $apiUserId, private string $apiKey)
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
            'user-id' => $this->apiUserId,
            'api-key' => $this->apiKey,
            'output-format' => 'json',
            'output-case' => 'snake',

            'user-agent' => $userAgent,
        ];

        $body = http_build_query($params, null, '&');

        $request = new Request('POST', self::$uri, ['Content-Type' => 'application/x-www-form-urlencoded'], $body);

        try {
            $response = $this->getResponse($request);
        } catch (Exception\RequestException $ex) {
            $prevEx = $ex->getPrevious();
            assert($prevEx instanceof ClientException);

            if (true === $prevEx->hasResponse() && 403 === $prevEx->getResponse()->getStatusCode()) {
                throw new Exception\InvalidCredentialsException('Your API userId "' . $this->apiUserId . '" and key "' . $this->apiKey . '" is not valid for ' . $this->getName(), null, $ex);
            }

            throw $ex;
        }

        /*
         * no json returned?
         */
        $contentType = $response->getHeader('Content-Type');
        if (!isset($contentType[0]) || 'application/json;charset=UTF-8' !== $contentType[0]) {
            throw new Exception\RequestException('Could not get valid "application/json" response from "' . $request->getUri() . '". Response is "' . $response->getBody()->getContents() . '"');
        }

        $content = json_decode($response->getBody()->getContents());

        /*
         * errors
         */
        if (isset($content->api_error)) {
            switch ($content->api_error) {
                case 1:
                    throw new Exception\RequestException('"' . $content->api_error_msg . '" response from "' . $request->getUri() . '". Response is "' . print_r($content, true) . '"');
                case 2:
                    throw new Exception\LimitationExceededException('Exceeded the maximum number of request with API userId "' . $this->apiUserId . '" and key "' . $this->apiKey . '" for ' . $this->getName());
                default:
                    throw new Exception\RequestException('"' . $content->api_error_msg . '" response from "' . $request->getUri() . '". Response is "' . print_r($content, true) . '"');
            }
        }

        /*
         * Missing data?
         */
        if (!$content instanceof stdClass) {
            throw new Exception\RequestException('Could not get valid response from "' . $request->getUri() . '". Response is "' . $response->getBody()->getContents() . '"');
        }

        return $content;
    }

    private function hasResult(stdClass $resultRaw): bool
    {
        return isset($resultRaw->type) && $this->isRealResult($resultRaw->type);
    }

    private function isBot(stdClass $resultRaw): bool
    {
        return isset($resultRaw->type) && 'robot' === $resultRaw->type;
    }

    private function hydrateBot(Model\Bot $bot, stdClass $resultRaw): void
    {
        $bot->setIsBot(true);

        if (!isset($resultRaw->browser_name)) {
            return;
        }

        $bot->setName($this->getRealResult($resultRaw->browser_name));
    }

    private function hydrateBrowser(Model\Browser $browser, stdClass $resultRaw): void
    {
        if (isset($resultRaw->browser_name)) {
            $browser->setName($this->getRealResult($resultRaw->browser_name, 'browser', 'name'));
        }

        if (!isset($resultRaw->version)) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->version));
    }

    private function hydrateOperatingSystem(Model\OperatingSystem $os, stdClass $resultRaw): void
    {
        if (isset($resultRaw->operating_system_family)) {
            $os->setName($this->getRealResult($resultRaw->operating_system_family));
        }

        if (!isset($resultRaw->operating_system_version)) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($resultRaw->operating_system_version));
    }

    private function hydrateDevice(Model\Device $device, stdClass $resultRaw): void
    {
        if (isset($resultRaw->mobile_model)) {
            $device->setModel($this->getRealResult($resultRaw->mobile_model, 'device', 'model'));
        }

        if (isset($resultRaw->mobile_brand)) {
            $device->setBrand($this->getRealResult($resultRaw->mobile_brand, 'device', 'brand'));
        }

        if (isset($resultRaw->type)) {
            $device->setType($this->getRealResult($resultRaw->type));
        }

        if (!isset($resultRaw->is_mobile) || true !== $resultRaw->is_mobile) {
            return;
        }

        $device->setIsMobile(true);
    }
}
