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
use UserAgentParserComparison\Exception\NoResultFoundException;

use function assert;
use function bin2hex;
use function file_get_contents;
use function is_array;
use function serialize;
use function sha1;

/** @see https://github.com/browscap/browscap-php */
final class Woothee extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'Woothee';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/woothee/woothee-php';

    /**
     * Composer package name
     */
    protected string $packageName = 'woothee/woothee';

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
            'name' => false,
            'version' => false,
        ],

        'device' => [
            'model' => false,
            'brand' => false,
            'type' => true,
            'isMobile' => false,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => false,
        ],
    ];

    /** @throws NoResultFoundException */
    public function getTests(): iterable
    {
        $path = 'vendor/woothee/woothee-testset/testsets';

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $files    = new class ($iterator, 'yaml') extends FilterIterator {
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

            $fixtureData = Yaml::parse(file_get_contents($file));

            if (!is_array($fixtureData)) {
                throw new Exception('wrong result!');
            }

            foreach ($fixtureData as $row) {
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

                if (!isset($row['target']) || '' === $row['target']) {
                    continue;
                }

                $result = $this->hydrateWoothee($data, $row);

                $key      = bin2hex(sha1($row['target'], true));
                $toInsert = [
                    'uaString' => $row['target'],
                    'result' => $result,
                ];

                yield $key => $toInsert;
            }
        }
    }

    private function hydrateWoothee(array $data, array $row): array
    {
        $data['resRawResult'] = serialize($row);

        if (isset($row['category']) && 'crawler' === $row['category']) {
            $data['resBotIsBot'] = 1;
            $data['resBotName']  = $row['name'];

            return $data;
        }

        if (isset($row['name']) && '' !== $row['name']) {
            $data['resBrowserName'] = $row['name'];
        }

        if (isset($row['version']) && '' !== $row['version']) {
            $data['resBrowserVersion'] = $row['version'];
        }

        if (isset($row['os']) && '' !== $row['os']) {
            $data['resOsName'] = $row['os'];
        }

        if (isset($row['os_version']) && '' !== $row['os_version']) {
            $data['resOsVersion'] = $row['os_version'];
        }

        if (isset($row['category']) && '' !== $row['category']) {
            $data['resDeviceType'] = $row['category'];
        }

        return $data;
    }
}
