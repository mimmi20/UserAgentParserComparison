<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use BrowscapHelper\Source\UaParserJsSource;
use RuntimeException;

use function bin2hex;
use function serialize;
use function sha1;
use function sprintf;

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
    protected string $language    = 'JS';

    /**
     * Set this in each Provider implementation
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    protected array $detectionCapabilities = [
        'bot' => [
            'isBot' => true,
            'name' => false,
            'type' => false,
        ],
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'brand' => true,
            'isMobile' => false,
            'isTouch' => false,
            'model' => true,
            'type' => false,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => false,
            'version' => false,
        ],
    ];

    /**
     * @return iterable<array<string, mixed>>
     * @phpstan-return iterable<string, array{resFilename: string, resRawResult: string, resBrowserName: string|null, resBrowserVersion: string|null, resEngineName: string|null, resEngineVersion: string|null, resOsName: string|null, resOsVersion: string|null, resDeviceModel: string|null, resDeviceBrand: string|null, resDeviceType: string|null, resDeviceIsMobile: bool|null, resDeviceIsTouch: bool|null, resBotIsBot: bool|null, resBotName: string|null, resBotType: string|null}>
     *
     * @throws RuntimeException
     */
    public function getTests(): iterable
    {
        $source        = new UaParserJsSource();
        $baseMessage   = sprintf('reading from source %s ', $source->getName());
        $messageLength = 0;

        if (!$source->isReady($baseMessage)) {
            return [];
        }

        foreach ($source->getProperties($baseMessage, $messageLength) as $test) {
            $key      = bin2hex(sha1($test['headers']['user-agent'], true));
            $toInsert = [
                'result' => [
                    'resBotIsBot' => null,
                    'resBotName' => null,
                    'resBotType' => null,

                    'resBrowserName' => $test['client']['name'],
                    'resBrowserVersion' => $test['client']['version'],
                    'resDeviceBrand' => $test['device']['brand'],
                    'resDeviceIsMobile' => $test['device']['ismobile'],
                    'resDeviceIsTouch' => $test['device']['display']['touch'],

                    'resDeviceModel' => $test['device']['deviceName'],
                    'resDeviceType' => $test['device']['type'],

                    'resEngineName' => $test['engine']['name'],
                    'resEngineVersion' => $test['engine']['version'],
                    'resFilename' => '',

                    'resOsName' => $test['platform']['name'],
                    'resOsVersion' => $test['platform']['version'],

                    'resRawResult' => serialize($test['raw'] ?? null),
                ],
                'uaString' => $test['headers']['user-agent'],
            ];

            yield $key => $toInsert;
        }
    }
}
