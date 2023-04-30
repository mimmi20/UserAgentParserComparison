<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use BrowscapHelper\Source\MobileDetectSource;
use RuntimeException;

use function bin2hex;
use function serialize;
use function sha1;
use function sprintf;

/** @see https://github.com/browscap/browscap-php */
final class MobileDetect extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'MobileDetect';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/serbanghita/Mobile-Detect';

    /**
     * Composer package name
     */
    protected string $packageName = 'mobiledetect/mobiledetectlib';
    protected string $language    = 'PHP';

    /**
     * Set this in each Provider implementation
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    protected array $detectionCapabilities = [
        'bot' => [
            'isBot' => false,
            'name' => false,
            'type' => false,
        ],
        'browser' => [
            'name' => false,
            'version' => false,
        ],

        'device' => [
            'brand' => false,
            'isMobile' => true,
            'isTouch' => false,
            'model' => true,
            'type' => false,
        ],

        'operatingSystem' => [
            'name' => false,
            'version' => false,
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
        $source        = new MobileDetectSource();
        $baseMessage   = sprintf('reading from source %s ', $source->getName());
        $messageLength = 0;

        if (!$source->isReady($baseMessage)) {
            return [];
        }

        foreach ($source->getProperties($baseMessage, $messageLength) as $test) {
            $key      = bin2hex(sha1((string) $test['headers']['user-agent'], true));
            $toInsert = [
                'result' => [
                    'resBotIsBot' => null,
                    'resBotName' => null,
                    'resBotType' => null,

                    'resBrowserName' => null,
                    'resBrowserVersion' => null,
                    'resDeviceBrand' => null,
                    'resDeviceIsMobile' => $test['device']['ismobile'],
                    'resDeviceIsTouch' => null,

                    'resDeviceModel' => $test['device']['deviceName'],
                    'resDeviceType' => null,

                    'resEngineName' => null,
                    'resEngineVersion' => null,
                    'resFilename' => $test['file'] ?? '',

                    'resOsName' => null,
                    'resOsVersion' => null,

                    'resRawResult' => serialize($test['raw'] ?? null),
                ],
                'uaString' => $test['headers']['user-agent'],
            ];

            yield $key => $toInsert;
        }
    }
}
