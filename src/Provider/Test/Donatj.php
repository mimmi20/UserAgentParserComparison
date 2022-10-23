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
     * @throws NoResultFoundException
     */
    public function getTests(): iterable
    {
        if (
            file_exists('vendor/donatj/phpuseragentparser/tests/user_agents.json')
            && 0 < filesize('vendor/donatj/phpuseragentparser/tests/user_agents.json')
        ) {
            $file = 'vendor/donatj/phpuseragentparser/tests/user_agents.json';
        } else {
            $file = 'vendor/donatj/phpuseragentparser/tests/user_agents.dist.json';
        }

        $content = file_get_contents($file);

        $json = json_decode($content, true);

        foreach ($json as $ua => $row) {
            $data = [
                'resFilename' => $file,

                'resBrowserName' => $row['browser'] ?? null,
                'resBrowserVersion' => $row['version'] ?? null,

                'resEngineName' => null,
                'resEngineVersion' => null,

                'resOsName' => $row['platform'] ?? null,
                'resOsVersion' => null,

                'resDeviceModel' => null,
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
