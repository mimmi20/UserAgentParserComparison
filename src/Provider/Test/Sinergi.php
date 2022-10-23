<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;

use function array_map;
use function bin2hex;
use function explode;
use function implode;
use function sha1;
use function simplexml_load_file;
use function trim;

/** @see https://github.com/browscap/browscap-php */
final class Sinergi extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'SinergiBrowserDetector';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/sinergi/php-browser-detector';

    /**
     * Composer package name
     */
    protected string $packageName = 'sinergi/browser-detector';

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
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'model' => true,
            'brand' => false,
            'type' => false,
            'isMobile' => true,
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
     * @throws NoResultFoundException
     */
    public function getTests(): iterable
    {
        $file = 'vendor/sinergi/browser-detector/tests/BrowserDetector/Tests/_files/UserAgentStrings.xml';

        $provider = simplexml_load_file($file);

        foreach ($provider->strings as $string) {
            foreach ($string as $field) {
                $ua = explode("\n", (string) $field->field[6]);
                $ua = array_map('trim', $ua);
                $ua = trim(implode(' ', $ua));

                $data = [
                    'resFilename' => $file,

                    'resBrowserName' => (string) $field->field[0],
                    'resBrowserVersion' => (string) $field->field[1],

                    'resEngineName' => null,
                    'resEngineVersion' => null,

                    'resOsName' => (string) $field->field[2],
                    'resOsVersion' => (string) $field->field[3],

                    'resDeviceModel' => (string) $field->field[4],
                    'resDeviceBrand' => null,
                    'resDeviceType' => null,
                    'resDeviceIsMobile' => null,
                    'resDeviceIsTouch' => null,

                    'resBotIsBot' => null,
                    'resBotName' => null,
                    'resBotType' => null,
                ];

                $key      = bin2hex(sha1($ua, true));
                $toInsert = [
                    'uaString' => $ua,
                    'result' => $data,
                ];

                yield $key => $toInsert;
            }
        }
    }
}
