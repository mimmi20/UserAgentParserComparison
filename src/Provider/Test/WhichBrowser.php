<?php
namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;
use WhichBrowser\Model\Version;
use WhichBrowser\Model\Main;
use WhichBrowser\Model\Family;
use WhichBrowser\Model\Using;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 * @see https://github.com/browscap/browscap-php
 */
class WhichBrowser extends AbstractTestProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected string $name = 'WhichBrowser';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected string $homepage = 'https://github.com/WhichBrowser/Parser';

    /**
     * Composer package name
     *
     * @var string
     */
    protected string $packageName = 'whichbrowser/parser';

    protected string $language = 'PHP';

    protected array $detectionCapabilities = [

        'browser' => [
            'name'    => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name'    => true,
            'version' => true,
        ],

        'operatingSystem' => [
            'name'    => true,
            'version' => true,
        ],

        'device' => [
            'model'    => true,
            'brand'    => true,
            'type'     => true,
            'isMobile' => true,
            'isTouch'  => false,
        ],

        'bot' => [
            'isBot' => true,
            'name'  => true,
            'type'  => false,
        ],
    ];

    /**
     * @throws NoResultFoundException
     *
     * @return iterable
     */
    public function getTests(): iterable
    {
        $path = 'vendor/whichbrowser/parser/tests/data';

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $files = new \RegexIterator($iterator, '/^.+\.yaml$/i', \RegexIterator::GET_MATCH);

        foreach ($files as $file) {

            $file = $file[0];

            $fixtureData = Yaml::parse(file_get_contents($file));

            if (! is_array($fixtureData)) {
                throw new \Exception('wrong result!');
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
                    'resBotType' => null
                ];

                try {
                    $result = $this->hydrateWhichbrowser($data, $row, $headers);
                } catch (\Exception $ex) {
                    continue;
                }

                $uaString = $headers['User-Agent'];

                $toInsert = [
                    'uaString' => $uaString,
                    'result' => $result
                ];

                if (count($headers) > 1) {
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
     * @param string|array $version
     * @return Version
     * @throws \Exception
     */
    private function getWhichbrowserVersion($version): Version
    {
        if (! is_array($version)) {
            $version = [
                'value' => $version
            ];
        }

        foreach ($version as $key => $value) {
            if (! in_array($key, [
                'value',
                'hidden',
                'nickname',
                'alias',
                'details',
                'builds'
            ])) {
                throw new \Exception('Unknown version key: ' . $key);
            }
        }

        return new Version($version);
    }

    private function hydrateWhichbrowser(array $data, array $row, array $headers): array
    {
        if (isset($row['engine']) || isset($row['features']) || isset($row['useragent'])) {
            throw new \Exception('client detection...');
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

                if ($key == 'name') {
                    $toUse['name'] = $value;
                } elseif ($key == 'type') {
                    $toUse['type'] = $value;
                } elseif ($key == 'alias') {
                    $toUse['alias'] = $value;
                } elseif ($key == 'version') {
                    $toUse['version'] = $this->getWhichbrowserVersion($value);
                } elseif ($key == 'using') {
                    $usingToUse = [];

                    if (! is_array($value)) {
                        $usingToUse['name'] = $value;
                    }
                    if (isset($value['name'])) {
                        $usingToUse['name'] = $value['name'];
                    }
                    if (isset($value['version'])) {
                        $usingToUse['version'] = $this->getWhichbrowserVersion($value['version']);
                    }

                    $toUse['using'] = new Using($usingToUse);
                } elseif ($key == 'family') {
                    $familyToUse = [];

                    if (! is_array($value)) {
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
                    throw new \Exception('unknown key: ' . $key . ' / ' . print_r($value, true));
                }
            }

            $main->browser->set($toUse);
        }

        if (isset($result['engine'])) {

            $toUse = [];

            foreach ($result['engine'] as $key => $value) {

                if ($key == 'name') {
                    $toUse['name'] = $value;
                } elseif ($key == 'version') {
                    $toUse['version'] = $this->getWhichbrowserVersion($value);
                } else {
                    throw new \Exception('unknown key: ' . $key . ' / ' . print_r($value, true));
                }
            }

            $main->engine->set($toUse);
        }

        if (isset($result['os'])) {

            $toUse = [];

            foreach ($result['os'] as $key => $value) {

                if ($key == 'name') {
                    $toUse['name'] = $value;
                } elseif ($key == 'alias') {
                    $toUse['alias'] = $value;
                } elseif ($key == 'family') {
                    $familyToUse = [];

                    if (! is_array($value)) {
                        $familyToUse['name'] = $value;
                    }
                    if (isset($value['name'])) {
                        $familyToUse['name'] = $value['name'];
                    }
                    if (isset($value['version'])) {
                        $familyToUse['version'] = $this->getWhichbrowserVersion($value['version']);
                    }

                    $toUse['family'] = new Family($familyToUse);
                } elseif ($key == 'version') {
                    $toUse['version'] = $this->getWhichbrowserVersion($value);
                } else {
                    throw new \Exception('unknown key: ' . $key . ' / ' . print_r($value, true));
                }
            }

            $main->os->set($toUse);
        }

        if (isset($result['device'])) {

            $toUse = [];

            foreach ($result['device'] as $key => $value) {

                if ($key == 'type') {
                    $toUse['type'] = $value;
                } elseif ($key == 'subtype') {
                    $toUse['subtype'] = $value;
                } elseif ($key == 'manufacturer') {
                    $toUse['manufacturer'] = $value;
                } elseif ($key == 'model') {
                    $toUse['model'] = $value;
                } elseif ($key == 'series') {
                    $toUse['series'] = $value;
                } elseif ($key == 'carrier') {
                    $toUse['carrier'] = $value;
                } else {
                    throw new \Exception('unknown key: ' . $key . ' / ' . $value);
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
        if ($main->getType() === 'bot') {
            $data['resBotIsBot'] = 1;

            if ($main->browser->getName() != '') {
                $data['resBotName'] = $main->browser->getName();
            }

            return $data;
        }

        if ($main->browser->getName() != '') {
            $data['resBrowserName'] = $main->browser->getName();

            if ($main->browser->getVersion() != '') {
                $data['resBrowserVersion'] = $main->browser->getVersion();
            }
        } elseif (isset($main->browser->using) && $main->browser->using instanceof \WhichBrowser\Model\Using && $main->browser->using->getName() != '') {
            $data['resBrowserName'] = $main->browser->using->getName();

            if ($main->browser->using->getVersion() != '') {
                $data['resBrowserVersion'] = $main->browser->using->getVersion();
            }
        }

        if ($main->engine->getName() != '') {
            $data['resEngineName'] = $main->engine->getName();
        }
        if ($main->engine->getVersion() != '') {
            $data['resEngineVersion'] = $main->engine->getVersion();
        }

        if ($main->os->getName() != '') {
            $data['resOsName'] = $main->os->getName();
        }
        if ($main->os->getVersion() != '') {
            $data['resOsVersion'] = $main->os->getVersion();
        }

        if ($main->device->getModel() != '') {
            $data['resDeviceModel'] = $main->device->getModel();
        }
        if ($main->device->getManufacturer() != '') {
            $data['resDeviceBrand'] = $main->device->getManufacturer();
        }
        if ($main->getType() != '') {
            $data['resDeviceType'] = $main->getType();
        }
        if ($main->isMobile() != '') {
            $data['resDeviceIsMobile'] = $main->isMobile();
        }

        return $data;
    }
}
