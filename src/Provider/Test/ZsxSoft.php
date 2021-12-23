<?php
namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;

/**
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 * @see https://github.com/browscap/browscap-php
 */
class ZsxSoft extends AbstractTestProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected string $name = 'Zsxsoft';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected string $homepage = 'https://github.com/zsxsoft/php-useragent';

    /**
     * Composer package name
     *
     * @var string
     */
    protected string $packageName = 'zsxsoft/php-useragent';

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
        $fixtureData = include 'vendor/zsxsoft/php-useragent/tests/UserAgentList.php';

        if (! is_array($fixtureData)) {
            throw new \Exception('wrong result!');
        }

        foreach ($fixtureData as $row) {

            $data = [
                'resFilename' => 'vendor/zsxsoft/php-useragent/tests/UserAgentList.php',

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

            $result = $this->hydrateZsxsoft($data, $row);

            $key = bin2hex(sha1($row[0][0], true));
            $toInsert = [
                'uaString' => $row[0][0],
                'result' => $result,
            ];

            yield $key => $toInsert;
        }
    }

    private function hydrateZsxsoft(array $data, array $row): array
    {
        $row = $row[1];

        $data['resRawResult'] = serialize($row);

        if (isset($row[2]) && $row[2] != '') {
            $data['resBrowserName'] = $row[2];
        }
        if (isset($row[3]) && $row[3] != '') {
            $data['resBrowserVersion'] = $row[3];
        }

        if (isset($row[5]) && $row[5] != '') {
            $data['resOsName'] = $row[5];
        }
        if (isset($row[6]) && $row[6] != '') {
            $data['resOsVersion'] = $row[6];
        }

        // if(isset($row[8]) && $row[8] != ''){
        // var_dump($row[8]);
        // }
        // if(isset($row[9]) && $row[9] != ''){
        // var_dump($row[9]);
        // }

        // var_dump($row);
        // var_dump($data);
        // exit();

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
