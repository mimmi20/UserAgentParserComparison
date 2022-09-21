<?php
namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;

/**
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 * @see https://github.com/browscap/browscap-php
 */
class Browscap extends AbstractTestProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected string $name = 'Browscap';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected string $homepage = 'https://github.com/browscap/browscap';

    /**
     * Composer package name
     *
     * @var string
     */
    protected string $packageName = 'browscap/browscap';

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
            'isTouch'  => true,
        ],

        'bot' => [
            'isBot' => true,
            'name'  => true,
            'type'  => true,
        ],
    ];

    /**
     * @throws NoResultFoundException
     *
     * @return iterable
     */
    public function getTests(): iterable
    {
        $path = 'vendor/browscap/browscap/tests/issues';

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $files = new class($iterator, 'php') extends \FilterIterator {
            private string $extension;

            public function __construct(\Iterator $iterator , string $extension)
            {
                parent::__construct($iterator);
                $this->extension = $extension;
            }

            public function accept(): bool
            {
                $file = $this->getInnerIterator()->current();

                assert($file instanceof \SplFileInfo);

                return $file->isFile() && $file->getExtension() === $this->extension;
            }
        };

        foreach ($files as $file) {
            assert($file instanceof \SplFileInfo);

            $file = $file->getPathname();

            $result = include $file;

            if (! is_array($result)) {
                throw new NoResultFoundException($file . ' did not return an array!');
            }

            foreach ($result as $row) {

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
                    $result = $this->hydrateBrowscap($data, $row);
                } catch (\Exception $ex) {
                    continue;
                }

                $key = bin2hex(sha1($row['ua'], true));
                $toInsert = [
                    'uaString' => $row['ua'],
                    'result' => $result,
                ];

                yield $key => $toInsert;
            }
        }
    }

    private function hydrateBrowscap(array $data, array $row): array
    {
        if (!$row['full']) {
            throw new \Exception('skip...');
        }

        $data['resRawResult'] = serialize($row['properties']);

        $row = $row['properties'];

        if (array_key_exists('Browser', $row) && stripos($row['Browser'], 'Fake') !== false) {
            throw new \Exception('skip...');
        }

        if (array_key_exists('Crawler', $row) && $row['Crawler'] === true) {
            $data['resBotIsBot'] = 1;

            if (array_key_exists('Browser', $row) && $row['Browser'] !== '') {
                $data['resBotName'] = $row['Browser'];
            }

            if (array_key_exists('Browser_Type', $row) && $row['Browser_Type'] !== '') {
                $data['resBotType'] = $row['Browser_Type'];
            }

            return $data;
        }

        if (array_key_exists('Browser', $row) && $row['Browser'] !== '') {
            $data['resBrowserName'] = $row['Browser'];
        }
        if (array_key_exists('Version', $row) && $row['Version'] !== '') {
            $data['resBrowserVersion'] = $row['Version'];
        }

        if (array_key_exists('RenderingEngine_Name', $row) && $row['RenderingEngine_Name'] !== '') {
            $data['resEngineName'] = $row['RenderingEngine_Name'];
        }
        if (array_key_exists('RenderingEngine_Version', $row) && $row['RenderingEngine_Version'] !== '') {
            $data['resEngineVersion'] = $row['RenderingEngine_Version'];
        }

        if (array_key_exists('Platform', $row) && $row['Platform'] !== '') {
            $data['resOsName'] = $row['Platform'];
        }
        if (array_key_exists('Platform_Version', $row) && $row['Platform_Version'] !== '') {
            $data['resOsVersion'] = $row['Platform_Version'];
        }

        if (array_key_exists('Device_Name', $row) && $row['Device_Name'] !== '') {
            $data['resDeviceModel'] = $row['Device_Name'];
        }
        if (array_key_exists('Device_Brand_Name', $row) && $row['Device_Brand_Name'] !== '') {
            $data['resDeviceBrand'] = $row['Device_Brand_Name'];
        }
        if (array_key_exists('Device_Type', $row) && $row['Device_Type'] !== '') {
            $data['resDeviceType'] = $row['Device_Type'];
        }
        if (array_key_exists('isMobileDevice', $row) && $row['isMobileDevice'] === true) {
            $data['resDeviceIsMobile'] = 1;
        }
        if (array_key_exists('Device_Pointing_Method', $row) && $row['Device_Pointing_Method'] === 'touchscreen') {
            $data['resDeviceIsTouch'] = 1;
        }

        return $data;
    }
}
