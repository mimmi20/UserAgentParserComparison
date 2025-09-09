<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use BrowscapHelper\Source\CrawlerDetectSource;
use LogicException;
use RuntimeException;

use function bin2hex;
use function serialize;
use function sha1;
use function sprintf;

/** @see https://github.com/browscap/browscap-php */
final class CrawlerDetect extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'crawler-detect';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/jaybizzle/crawler-detect';

    /**
     * Composer package name
     */
    protected string $packageName = 'jaybizzle/crawler-detect';
    protected string $language    = 'PHP';

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
            'name' => false,
            'version' => false,
        ],

        'device' => [
            'brand' => false,
            'isMobile' => false,
            'isTouch' => false,
            'model' => false,
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
     * @throws LogicException
     * @throws RuntimeException
     */
    public function getTests(): iterable
    {
        $source        = new CrawlerDetectSource();
        $baseMessage   = sprintf('reading from source %s ', $source->getName());
        $messageLength = 0;

        if (!$source->isReady($baseMessage)) {
            return [];
        }

        foreach ($source->getProperties($baseMessage, $messageLength) as $test) {
            $key      = bin2hex(sha1(var_export($test['headers'], true), true));
            $toInsert = [
                'result' => [
                    'resBotIsBot' => $test['client']['isbot'],
                    'resBotName' => null,
                    'resBotType' => null,

                    'resBrowserName' => null,
                    'resBrowserVersion' => null,
                    'resDeviceBrand' => null,
                    'resDeviceIsMobile' => null,
                    'resDeviceIsTouch' => null,

                    'resDeviceModel' => null,
                    'resDeviceType' => null,

                    'resEngineName' => null,
                    'resEngineVersion' => null,
                    'resFilename' => $test['file'] ?? '',

                    'resOsName' => null,
                    'resOsVersion' => null,

                    'resRawResult' => serialize($test['raw'] ?? null),
                ],
                'headers' => $test['headers'],
            ];

            yield $key => $toInsert;
        }
    }
}
