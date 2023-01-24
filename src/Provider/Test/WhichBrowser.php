<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use Exception;
use FilterIterator;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use UserAgentParserComparison\Exception\NoResultFoundException;
use WhichBrowser\Model\Family;
use WhichBrowser\Model\Main;
use WhichBrowser\Model\Using;
use WhichBrowser\Model\Version;

use function array_key_exists;
use function assert;
use function bin2hex;
use function count;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function mb_strpos;
use function print_r;
use function serialize;
use function sha1;
use function str_replace;

/** @see https://github.com/browscap/browscap-php */
final class WhichBrowser extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'WhichBrowser';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/WhichBrowser/Parser';

    /**
     * Composer package name
     */
    protected string $packageName = 'whichbrowser/parser';

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
            'isMobile' => true,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
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
        $source = new \BrowscapHelper\Source\WhichBrowserSource();
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
                    'resFilename' => $test['file'] ?? '',

                    'resRawResult' => serialize($test['raw'] ?? null),

                    'resBrowserName' => $test['client']['isbot'] ? null : $test['client']['name'],
                    'resBrowserVersion' => $test['client']['isbot'] ? null : $test['client']['version'],

                    'resEngineName' => $test['engine']['name'],
                    'resEngineVersion' => $test['engine']['version'],

                    'resOsName' => $test['platform']['name'],
                    'resOsVersion' => $test['platform']['version'],

                    'resDeviceModel' => $test['device']['deviceName'],
                    'resDeviceBrand' => $test['device']['brand'],
                    'resDeviceType' => $test['device']['type'],
                    'resDeviceIsMobile' => $test['device']['ismobile'],
                    'resDeviceIsTouch' => $test['device']['display']['touch'],

                    'resBotIsBot' => $test['client']['isbot'],
                    'resBotName' => $test['client']['isbot'] ? $test['client']['name'] : null,
                    'resBotType' => $test['client']['isbot'] ? $test['client']['type'] : null,
                ],
            ];

            yield $key => $toInsert;
        }
    }
}
