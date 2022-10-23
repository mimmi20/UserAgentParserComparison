<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;

use function bin2hex;
use function is_array;
use function serialize;
use function sha1;

/** @see https://github.com/browscap/browscap-php */
final class ZsxSoft extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'Zsxsoft';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/zsxsoft/php-useragent';

    /**
     * Composer package name
     */
    protected string $packageName = 'zsxsoft/php-useragent';

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
            'isTouch' => true,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => true,
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
        $fixtureData = include 'vendor/zsxsoft/php-useragent/tests/UserAgentList.php';

        if (!is_array($fixtureData)) {
            throw new NoResultFoundException('wrong result!');
        }

        foreach ($fixtureData as $row) {
            $data = [
                'resFilename' => 'vendor/zsxsoft/php-useragent/tests/UserAgentList.php',

                'resRawResult' => serialize(null),

                'resBrowserName' => null,
                'resBrowserVersion' => null,

                'resEngineName' => null,
                'resEngineVersion' => null,

                'resOsName' => null,
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

            $result = $this->hydrateZsxsoft($data, $row);

            $key      = bin2hex(sha1($row[0][0], true));
            $toInsert = [
                'uaString' => $row[0][0],
                'result' => $result,
            ];

            yield $key => $toInsert;
        }
    }

    /**
     * @return iterable<array<string, mixed>>
     * @phpstan-return iterable<string, array{resFilename: string, resRawResult: string, resBrowserName: string|null, resBrowserVersion: string|null, resEngineName: string|null, resEngineVersion: string|null, resOsName: string|null, resOsVersion: string|null, resDeviceModel: string|null, resDeviceBrand: string|null, resDeviceType: string|null, resDeviceIsMobile: bool|null, resDeviceIsTouch: bool|null, resBotIsBot: bool|null, resBotName: string|null, resBotType: string|null}>
     */
    private function hydrateZsxsoft(array $data, array $row): array
    {
        $row = $row[1];

        $data['resRawResult'] = serialize($row);

        if (isset($row[2]) && '' !== $row[2]) {
            $data['resBrowserName'] = $row[2];
        }

        if (isset($row[3]) && '' !== $row[3]) {
            $data['resBrowserVersion'] = $row[3];
        }

        if (isset($row[5]) && '' !== $row[5]) {
            $data['resOsName'] = $row[5];
        }

        if (isset($row[6]) && '' !== $row[6]) {
            $data['resOsVersion'] = $row[6];
        }

        // 0 => browser image
        // 1 => os image
        // 2 => browser name
        // 3 => browser version
        // 4 => browser title
        // 5 => os name
        // 6 => os version
        // 7 => os title
        // 8 => device title
        // 9 => platform type

        return $data;
    }
}
