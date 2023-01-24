<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;

use function bin2hex;
use function file_exists;
use function file_get_contents;
use function filesize;
use function json_decode;
use function sha1;

/** @see https://github.com/browscap/browscap-php */
final class Endorphin extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'Endorphin Browser Detector';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/endorphin-studio/browser-detector';

    /**
     * Composer package name
     */
    protected string $packageName = 'endorphin-studio/browser-detector';

    protected string $language = 'PHP';

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
            'name' => false,
            'version' => false,
        ],

        'device' => [
            'model' => false,
            'brand' => false,
            'type' => false,
            'isMobile' => false,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => false,
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
        $source = new \BrowscapHelper\Source\EndorphinSource();
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

                    'resBrowserName' => isset($test['client']['isbot']) ? null : $test['client']['name'],
                    'resBrowserVersion' => isset($test['client']['isbot']) ? null : ($test['client']['version'] ?? null),

                    'resEngineName' => $test['engine']['name'],
                    'resEngineVersion' => $test['engine']['version'],

                    'resOsName' => $test['platform']['name'],
                    'resOsVersion' => $test['platform']['version'] ?? null,

                    'resDeviceModel' => $test['device']['deviceName'] ?? null,
                    'resDeviceBrand' => $test['device']['brand'] ?? null,
                    'resDeviceType' => $test['device']['type'] ?? null,
                    'resDeviceIsMobile' => $test['device']['ismobile'] ?? null,
                    'resDeviceIsTouch' => null,

                    'resBotIsBot' => $test['client']['isbot'] ?? null,
                    'resBotName' => isset($test['client']['isbot']) ? $test['client']['name'] : null,
                    'resBotType' => null,
                ],
            ];

            yield $key => $toInsert;
        }
    }
}
