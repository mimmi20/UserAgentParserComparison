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
    protected $name = 'Browscap';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected $homepage = 'https://github.com/browscap/browscap-php';

    /**
     * Composer package name
     *
     * @var string
     */
    protected $packageName = 'browscap/browscap-php';

    protected $detectionCapabilities = [

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
        $files = new \RegexIterator($iterator, '/^.+\.php$/i', \RegexIterator::GET_MATCH);

        foreach ($files as $file) {

            $file = $file[0];

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
        $data['resRawResult'] = serialize($row['properties']);

        $row = $row['properties'];

        if (isset($row['Browser']) && stripos($row['Browser'], 'Fake') !== false) {
            throw new \Exception('skip...');
        }

        if (isset($row['Crawler']) && $row['Crawler'] === true) {
            $data['resBotIsBot'] = 1;

            if (isset($row['Browser']) && $row['Browser'] != '') {
                $data['resBotName'] = $row['Browser'];
            }

            if (isset($row['Browser_Type']) && $row['Browser_Type'] != '') {
                $data['resBotType'] = $row['Browser_Type'];
            }

            return $data;
        }

        if (isset($row['Browser']) && $row['Browser'] != '') {
            $data['resBrowserName'] = $row['Browser'];
        }
        if (isset($row['Version']) && $row['Version'] != '') {
            $data['resBrowserVersion'] = $row['Version'];
        }

        if (isset($row['RenderingEngine_Name']) && $row['RenderingEngine_Name'] != '') {
            $data['resEngineName'] = $row['RenderingEngine_Name'];
        }
        if (isset($row['RenderingEngine_Version']) && $row['RenderingEngine_Version'] != '') {
            $data['resEngineVersion'] = $row['RenderingEngine_Version'];
        }

        if (isset($row['Platform']) && $row['Platform'] != '') {
            $data['resOsName'] = $row['Platform'];
        }
        if (isset($row['Platform_Version']) && $row['Platform_Version'] != '') {
            $data['resOsVersion'] = $row['Platform_Version'];
        }

        if (isset($row['Device_Name']) && $row['Device_Name'] != '') {
            $data['resDeviceModel'] = $row['Device_Name'];
        }
        if (isset($row['Device_Brand_Name']) && $row['Device_Brand_Name'] != '') {
            $data['resDeviceBrand'] = $row['Device_Brand_Name'];
        }
        if (isset($row['Device_Type']) && $row['Device_Type'] != '') {
            $data['resDeviceType'] = $row['Device_Type'];
        }
        if (isset($row['isMobileDevice']) && $row['isMobileDevice'] != '') {
            $data['resDeviceIsMobile'] = $row['isMobileDevice'];
        }
        if (isset($row['Device_Pointing_Method']) && $row['Device_Pointing_Method'] == 'touchscreen') {
            $data['resDeviceIsTouch'] = 1;
        }

        return $data;
    }
}
