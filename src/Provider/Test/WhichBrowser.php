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
     * @throws NoResultFoundException
     */
    public function getTests(): iterable
    {
        $path = 'vendor/whichbrowser/parser/tests/data';

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
                throw new NoResultFoundException('wrong result!');
            }

            foreach ($fixtureData as $row) {
                if (!array_key_exists('headers', $row)) {
                    continue;
                }

                if (is_array($row['headers'])) {
                    $headers = $row['headers'];
                } elseif (is_string($row['headers']) && 0 === mb_strpos($row['headers'], 'User-Agent: ')) {
                    $headers = ['User-Agent' => str_replace('User-Agent: ', '', $row['headers'])];
                } else {
                    continue;
                }

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
                    $result = $this->hydrateWhichbrowser($data, $row);
                } catch (Throwable) {
                    continue;
                }

                $uaString = $headers['User-Agent'];

                $toInsert = [
                    'uaString' => $uaString,
                    'result' => $result,
                ];

                if (1 < count($headers)) {
                    unset($headers['User-Agent']);

                    $toInsert['uaAdditionalHeaders'] = $headers;

                    $key = bin2hex(sha1($uaString . ' ' . json_encode($headers), true));
                } else {
                    $key = bin2hex(sha1($uaString, true));
                }

                yield $key => $toInsert;
            }
        }
    }

    /**
     * @param array|string $version
     *
     * @throws Exception
     */
    private function getWhichbrowserVersion($version): Version
    {
        if (!is_array($version)) {
            $version = ['value' => $version];
        }

        foreach ($version as $key => $value) {
            if (
                !in_array($key, [
                    'value',
                    'hidden',
                    'nickname',
                    'alias',
                    'details',
                    'builds',
                ], true)
            ) {
                throw new Exception('Unknown version key: ' . $key);
            }
        }

        return new Version($version);
    }

    /**
     * @return iterable<array<string, mixed>>
     * @phpstan-return iterable<string, array{resFilename: string, resRawResult: string, resBrowserName: string|null, resBrowserVersion: string|null, resEngineName: string|null, resEngineVersion: string|null, resOsName: string|null, resOsVersion: string|null, resDeviceModel: string|null, resDeviceBrand: string|null, resDeviceType: string|null, resDeviceIsMobile: bool|null, resDeviceIsTouch: bool|null, resBotIsBot: bool|null, resBotName: string|null, resBotType: string|null}>
     *
     * @throws Exception
     */
    private function hydrateWhichbrowser(array $data, array $row): array
    {
        if (isset($row['engine']) || isset($row['features']) || isset($row['useragent'])) {
            throw new Exception('client detection...');
        }

        $data['resRawResult'] = serialize($row);

        $result = $row['result'];

        /*
         * Hydrate...
         */
        $main = new Main();

        if (isset($result['browser'])) {
            $toUse = [];

            foreach ($result['browser'] as $key => $value) {
                if ('name' === $key) {
                    $toUse['name'] = $value;
                } elseif ('type' === $key) {
                    $toUse['type'] = $value;
                } elseif ('alias' === $key) {
                    $toUse['alias'] = $value;
                } elseif ('version' === $key) {
                    $toUse['version'] = $this->getWhichbrowserVersion($value);
                } elseif ('using' === $key) {
                    $usingToUse = [];

                    if (!is_array($value)) {
                        $usingToUse['name'] = $value;
                    }

                    if (isset($value['name'])) {
                        $usingToUse['name'] = $value['name'];
                    }

                    if (isset($value['version'])) {
                        $usingToUse['version'] = $this->getWhichbrowserVersion($value['version']);
                    }

                    $toUse['using'] = new Using($usingToUse);
                } elseif ('family' === $key) {
                    $familyToUse = [];

                    if (!is_array($value)) {
                        $familyToUse['name'] = $value;
                    }

                    if (isset($value['name'])) {
                        $familyToUse['name'] = $value['name'];
                    }

                    if (isset($value['version'])) {
                        $familyToUse['version'] = $this->getWhichbrowserVersion($value['version']);
                    }

                    $toUse['family'] = new Family($familyToUse);
                } else {
                    throw new Exception('unknown key: ' . $key . ' / ' . print_r($value, true));
                }
            }

            $main->browser->set($toUse);
        }

        if (isset($result['engine'])) {
            $toUse = [];

            foreach ($result['engine'] as $key => $value) {
                if ('name' === $key) {
                    $toUse['name'] = $value;
                } elseif ('version' === $key) {
                    $toUse['version'] = $this->getWhichbrowserVersion($value);
                } else {
                    throw new Exception('unknown key: ' . $key . ' / ' . print_r($value, true));
                }
            }

            $main->engine->set($toUse);
        }

        if (isset($result['os'])) {
            $toUse = [];

            foreach ($result['os'] as $key => $value) {
                if ('name' === $key) {
                    $toUse['name'] = $value;
                } elseif ('alias' === $key) {
                    $toUse['alias'] = $value;
                } elseif ('family' === $key) {
                    $familyToUse = [];

                    if (!is_array($value)) {
                        $familyToUse['name'] = $value;
                    }

                    if (isset($value['name'])) {
                        $familyToUse['name'] = $value['name'];
                    }

                    if (isset($value['version'])) {
                        $familyToUse['version'] = $this->getWhichbrowserVersion($value['version']);
                    }

                    $toUse['family'] = new Family($familyToUse);
                } elseif ('version' === $key) {
                    $toUse['version'] = $this->getWhichbrowserVersion($value);
                } else {
                    throw new Exception('unknown key: ' . $key . ' / ' . print_r($value, true));
                }
            }

            $main->os->set($toUse);
        }

        if (isset($result['device'])) {
            $toUse = [];

            foreach ($result['device'] as $key => $value) {
                if ('type' === $key) {
                    $toUse['type'] = $value;
                } elseif ('subtype' === $key) {
                    $toUse['subtype'] = $value;
                } elseif ('manufacturer' === $key) {
                    $toUse['manufacturer'] = $value;
                } elseif ('model' === $key) {
                    $toUse['model'] = $value;
                } elseif ('series' === $key) {
                    $toUse['series'] = $value;
                } elseif ('carrier' === $key) {
                    $toUse['carrier'] = $value;
                } else {
                    throw new Exception('unknown key: ' . $key . ' / ' . $value);
                }
            }

            $main->device->setIdentification($toUse);
        }

        if (isset($result['camouflage'])) {
            $main->camouflage = $result['camouflage'];
        }

        /*
         * convert to our result
         */
        if ('bot' === $main->getType()) {
            $data['resBotIsBot'] = 1;

            if ('' !== $main->browser->getName()) {
                $data['resBotName'] = $main->browser->getName();
            }

            return $data;
        }

        if ('' !== $main->browser->getName()) {
            $data['resBrowserName'] = $main->browser->getName();

            if ('' !== $main->browser->getVersion()) {
                $data['resBrowserVersion'] = $main->browser->getVersion();
            }
        } elseif (isset($main->browser->using) && $main->browser->using instanceof Using && '' !== $main->browser->using->getName()) {
            $data['resBrowserName'] = $main->browser->using->getName();

            if ('' !== $main->browser->using->getVersion()) {
                $data['resBrowserVersion'] = $main->browser->using->getVersion();
            }
        }

        if ('' !== $main->engine->getName()) {
            $data['resEngineName'] = $main->engine->getName();
        }

        if ('' !== $main->engine->getVersion()) {
            $data['resEngineVersion'] = $main->engine->getVersion();
        }

        if ('' !== $main->os->getName()) {
            $data['resOsName'] = $main->os->getName();
        }

        if ('' !== $main->os->getVersion()) {
            $data['resOsVersion'] = $main->os->getVersion();
        }

        if ('' !== $main->device->getModel()) {
            $data['resDeviceModel'] = $main->device->getModel();
        }

        if ('' !== $main->device->getManufacturer()) {
            $data['resDeviceBrand'] = $main->device->getManufacturer();
        }

        if ('' !== $main->getType()) {
            $data['resDeviceType'] = $main->getType();
        }

        if ('' !== $main->isMobile()) {
            $data['resDeviceIsMobile'] = $main->isMobile();
        }

        return $data;
    }
}
