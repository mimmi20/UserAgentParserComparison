<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use BrowscapHelper\Source\DonatjSource;
use LogicException;
use RuntimeException;

use function bin2hex;
use function serialize;
use function sha1;
use function sprintf;

/** @see https://github.com/browscap/browscap-php */
final class Donatj extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'DonatjUAParser';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/donatj/PhpUserAgent';

    /**
     * Composer package name
     */
    protected string $packageName = 'donatj/phpuseragentparser';
    protected string $language    = 'PHP';

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
     * @throws LogicException
     * @throws RuntimeException
     */
    public function getTests(): iterable
    {
        $source        = new DonatjSource();
        $baseMessage   = sprintf('reading from source %s ', $source->getName());
        $messageLength = 0;

        if (!$source->isReady($baseMessage)) {
            return [];
        }

        foreach ($source->getProperties($baseMessage, $messageLength) as $test) {
            $key      = bin2hex(sha1($test['headers']['user-agent'], true));
            $toInsert = [
                'uaString' => $test['headers']['user-agent'],
                'result' => [
                    'resFilename' => $test['file'] ?? '',

                    'resRawResult' => serialize($test['raw'] ?? null),

                    'resBrowserName' => $test['client']['name'],
                    'resBrowserVersion' => $test['client']['version'],

                    'resEngineName' => null,
                    'resEngineVersion' => null,

                    'resOsName' => $test['platform']['name'],
                    'resOsVersion' => null,

                    'resDeviceModel' => null,
                    'resDeviceBrand' => null,
                    'resDeviceType' => null,
                    'resDeviceIsMobile' => null,
                    'resDeviceIsTouch' => null,

                    'resBotIsBot' => null,
                    'resBotName' => null,
                    'resBotType' => null,
                ],
            ];

            yield $key => $toInsert;
        }
    }
}
