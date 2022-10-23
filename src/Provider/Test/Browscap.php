<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use Exception;
use FilterIterator;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;
use UserAgentParserComparison\Exception\NoResultFoundException;

use function array_key_exists;
use function assert;
use function bin2hex;
use function is_array;
use function mb_stripos;
use function serialize;
use function sha1;

/** @see https://github.com/browscap/browscap-php */
final class Browscap extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'Browscap';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/browscap/browscap';

    /**
     * Composer package name
     */
    protected string $packageName = 'browscap/browscap';

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
        $path = 'vendor/browscap/browscap/tests/issues';

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $files    = new class ($iterator, 'php') extends FilterIterator {
            public function __construct(Iterator $iterator, private string $extension)
            {
                parent::__construct($iterator);
            }

            public function accept(): bool
            {
                $file = $this->getInnerIterator()->current();

                assert($file instanceof SplFileInfo);

                return $file->isFile() && $file->getExtension() === $this->extension;
            }
        };

        foreach ($files as $file) {
            assert($file instanceof SplFileInfo);

            $file = $file->getPathname();

            $result = include $file;

            if (!is_array($result)) {
                throw new NoResultFoundException($file . ' did not return an array!');
            }

            foreach ($result as $row) {
                $data = [
                    'resFilename' => $file,

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

                try {
                    $result = $this->hydrateBrowscap($data, $row);
                } catch (Throwable) {
                    continue;
                }

                $key      = bin2hex(sha1($row['ua'], true));
                $toInsert = [
                    'uaString' => $row['ua'],
                    'result' => $result,
                ];

                yield $key => $toInsert;
            }
        }
    }

    /**
     * @return iterable<array<string, mixed>>
     * @phpstan-return iterable<string, array{resFilename: string, resRawResult: string, resBrowserName: string|null, resBrowserVersion: string|null, resEngineName: string|null, resEngineVersion: string|null, resOsName: string|null, resOsVersion: string|null, resDeviceModel: string|null, resDeviceBrand: string|null, resDeviceType: string|null, resDeviceIsMobile: bool|null, resDeviceIsTouch: bool|null, resBotIsBot: bool|null, resBotName: string|null, resBotType: string|null}>
     *
     * @throws Exception
     */
    private function hydrateBrowscap(array $data, array $row): array
    {
        if (!$row['full']) {
            throw new Exception('skip...');
        }

        $data['resRawResult'] = serialize($row['properties']);

        $row = $row['properties'];

        if (array_key_exists('Browser', $row) && false !== mb_stripos($row['Browser'], 'Fake')) {
            throw new Exception('skip...');
        }

        if (array_key_exists('Crawler', $row) && true === $row['Crawler']) {
            $data['resBotIsBot'] = 1;

            if (array_key_exists('Browser', $row) && '' !== $row['Browser']) {
                $data['resBotName'] = $row['Browser'];
            }

            if (array_key_exists('Browser_Type', $row) && '' !== $row['Browser_Type']) {
                $data['resBotType'] = $row['Browser_Type'];
            }

            return $data;
        }

        if (array_key_exists('Browser', $row) && '' !== $row['Browser']) {
            $data['resBrowserName'] = $row['Browser'];
        }

        if (array_key_exists('Version', $row) && '' !== $row['Version']) {
            $data['resBrowserVersion'] = $row['Version'];
        }

        if (array_key_exists('RenderingEngine_Name', $row) && '' !== $row['RenderingEngine_Name']) {
            $data['resEngineName'] = $row['RenderingEngine_Name'];
        }

        if (array_key_exists('RenderingEngine_Version', $row) && '' !== $row['RenderingEngine_Version']) {
            $data['resEngineVersion'] = $row['RenderingEngine_Version'];
        }

        if (array_key_exists('Platform', $row) && '' !== $row['Platform']) {
            $data['resOsName'] = $row['Platform'];
        }

        if (array_key_exists('Platform_Version', $row) && '' !== $row['Platform_Version']) {
            $data['resOsVersion'] = $row['Platform_Version'];
        }

        if (array_key_exists('Device_Name', $row) && '' !== $row['Device_Name']) {
            $data['resDeviceModel'] = $row['Device_Name'];
        }

        if (array_key_exists('Device_Brand_Name', $row) && '' !== $row['Device_Brand_Name']) {
            $data['resDeviceBrand'] = $row['Device_Brand_Name'];
        }

        if (array_key_exists('Device_Type', $row) && '' !== $row['Device_Type']) {
            $data['resDeviceType'] = $row['Device_Type'];
        }

        if (array_key_exists('isMobileDevice', $row) && true === $row['isMobileDevice']) {
            $data['resDeviceIsMobile'] = 1;
        }

        if (array_key_exists('Device_Pointing_Method', $row) && 'touchscreen' === $row['Device_Pointing_Method']) {
            $data['resDeviceIsTouch'] = 1;
        }

        return $data;
    }
}
