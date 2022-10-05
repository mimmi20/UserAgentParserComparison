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
final class UapCore extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'UAParser';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/ua-parser/uap-core';

    /**
     * Composer package name
     */
    protected string $packageName = 'thadafinser/uap-core';

    protected string $language = 'PHP';

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

    /** @throws NoResultFoundException */
    public function getTests(): iterable
    {
        $path = 'vendor/thadafinser/uap-core/tests';

        /*
         * UA (browser)
         */
        $file = $path . '/test_ua.yaml';

        $fixtureData = Yaml::parse(file_get_contents($file));

        if (!is_array($fixtureData) || !isset($fixtureData['test_cases'])) {
            throw new Exception('wrong result!');
        }

        foreach ($fixtureData['test_cases'] as $row) {
            $data = [
                'resFilename' => $file,

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

            try {
                $result = $this->hydrateUapCore($data, $row, 'browser');

                yield bin2hex(sha1($row['user_agent_string'], true)) => [
                    'uaString' => $row['user_agent_string'],
                    'result' => $result,
                ];
            } catch (Throwable) {
                // do nothing
            }
        }

        /*
         * OS
         */
        $file = $path . '/test_os.yaml';

        $fixtureData = Yaml::parse(file_get_contents($file));

        if (!is_array($fixtureData) || !isset($fixtureData['test_cases'])) {
            throw new Exception('wrong result!');
        }

        foreach ($fixtureData['test_cases'] as $row) {
            $data = [
                'resFilename' => $file,

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

            try {
                $result = $this->hydrateUapCore($data, $row, 'os');

                yield bin2hex(sha1($row['user_agent_string'], true)) => [
                    'uaString' => $row['user_agent_string'],
                    'result' => $result,
                ];
            } catch (Throwable) {
                // do nothing
            }
        }

        /*
         * Device
         */
        $file = $path . '/test_device.yaml';

        $fixtureData = Yaml::parse(file_get_contents($file));

        if (!is_array($fixtureData) || !isset($fixtureData['test_cases'])) {
            throw new Exception('wrong result!');
        }

        foreach ($fixtureData['test_cases'] as $row) {
            $data = [
                'resFilename' => $file,

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

            try {
                $result = $this->hydrateUapCore($data, $row, 'device');
            } catch (Throwable) {
                continue;
            }

            $key      = bin2hex(sha1($row['user_agent_string'], true));
            $toInsert = [
                'uaString' => $row['user_agent_string'],
                'result' => $result,
            ];

            yield $key => $toInsert;
        }
    }

    private function hydrateUapCore(array $data, array $row, string $type): array
    {
        $data['resRawResult'] = serialize($row);

        if ('os' === $type) {
            if ('Other' === $row['family']) {
                throw new Exception('skip...');
            }

            $data['resOsName'] = $row['family'];

            if ('' !== $row['major']) {
                $version = $row['major'];
                if ('' !== $row['minor']) {
                    $version .= '.' . $row['minor'];

                    if ('' !== $row['patch']) {
                        $version .= '.' . $row['patch'];

                        if ('' !== $row['patch_minor']) {
                            $version .= '.' . $row['patch_minor'];
                        }
                    }
                }

                $data['resOsVersion'] = $version;
            }

            return $data;
        }

        if ('browser' === $type) {
            $data['resBrowserName'] = $row['family'];

            if ('' !== $row['major']) {
                $version = $row['major'];
                if ('' !== $row['minor']) {
                    $version .= '.' . $row['minor'];

                    if ('' !== $row['patch']) {
                        $version .= '.' . $row['patch'];
                    }
                }

                $data['resBrowserVersion'] = $version;
            }

            return $data;
        }

        if ('device' === $type) {
            if ('Spider' === $row['family']) {
                $data['resBotIsBot'] = 1;

                return $data;
            }

            if ('' !== $row['brand']) {
                $data['resDeviceBrand'] = $row['brand'];
            }

            if ('' !== $row['model']) {
                $data['resDeviceModel'] = $row['model'];
            }

            return $data;
        }

        throw new Exception('unknown type: ' . $type);
    }
}
