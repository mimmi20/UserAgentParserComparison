<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use Exception;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use UserAgentParserComparison\Exception\NoResultFoundException;

use function bin2hex;
use function file_get_contents;
use function is_array;
use function serialize;
use function sha1;

/** @see https://github.com/browscap/browscap-php */
final class UaParserJs extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'ua-parser-js';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/faisalman/ua-parser-js';

    /**
     * Composer package name
     */
    protected string $packageName = 'ua-parser-js';

    protected string $language = 'JS';

    /**
     * Set this in each Provider implementation
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
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
            'type' => false,
            'isMobile' => false,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => false,
            'type' => false,
        ],
    ];

    /**
     * @return iterable<array<string, mixed>>
     * @phpstan-return iterable<string, array{resFilename: string, resRawResult: string, resBrowserName: string|null, resBrowserVersion: string|null, resEngineName: string|null, resEngineVersion: string|null, resOsName: string|null, resOsVersion: string|null, resDeviceModel: string|null, resDeviceBrand: string|null, resDeviceType: string|null, resDeviceIsMobile: bool|null, resDeviceIsTouch: bool|null, resBotIsBot: bool|null, resBotName: string|null, resBotType: string|null}>
     *
     * @throws \RuntimeException
     */
    public function getTests(): iterable
    {
        $source = new \BrowscapHelper\Source\UaParserJsSource();
        $baseMessage = sprintf('reading from source %s ', $source->getName());
        $messageLength = 0;

        if (!$source->isReady($baseMessage)) {
            return [];
        }

        foreach ($source->getProperties($baseMessage, $messageLength) as $test) {
            $key      = bin2hex(sha1($test['headers']['user-agent'], true));
            $toInsert = [
                'uaString' => $test['headers']['user-agent'],
                'result' => [
                    'resFilename' => '',

                    'resRawResult' => serialize($test['raw'] ?? null),

                    'resBrowserName' => $test['client']['name'],
                    'resBrowserVersion' => $test['client']['version'],

                    'resEngineName' => $test['engine']['name'],
                    'resEngineVersion' => $test['engine']['version'],

                    'resOsName' => $test['platform']['name'],
                    'resOsVersion' => $test['platform']['version'],

                    'resDeviceModel' => $test['device']['deviceName'],
                    'resDeviceBrand' => $test['device']['brand'],
                    'resDeviceType' => $test['device']['type'],
                    'resDeviceIsMobile' => $test['device']['ismobile'],
                    'resDeviceIsTouch' => $test['device']['display']['touch'],

                    'resBotIsBot' => null,
                    'resBotName' => null,
                    'resBotType' => null,
                ],
            ];

            yield $key => $toInsert;
        }
    }
}
