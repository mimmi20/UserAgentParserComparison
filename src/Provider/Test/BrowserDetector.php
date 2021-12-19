<?php
namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;

/**
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 * @see https://github.com/browscap/browscap-php
 */
class BrowserDetector extends AbstractTestProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected $name = 'BrowserDetector';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected $homepage = 'https://github.com/mimmi20/browser-detector';

    /**
     * Composer package name
     *
     * @var string
     */
    protected $packageName = 'mimmi20/browser-detector';

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
            'isTouch'  => false,
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
        $path = 'vendor/mimmi20/browser-detector/tests/data';

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $files = new \RegexIterator($iterator, '/^.+\.json$/i', \RegexIterator::GET_MATCH);

        foreach ($files as $file) {

            $file = $file[0];

            $content = file_get_contents($file);

            if ('' === $content || PHP_EOL === $content) {
                continue;
            }

            try {
                $allEncodedData = json_decode(
                    $content,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $e) {
                continue;
            }

            if (!is_array($allEncodedData)) {
                continue;
            }

            foreach ($allEncodedData as $encodedData) {
                $ua = $encodedData['headers']['user-agent'] ?? '';

                if ('' === $ua) {
                    continue;
                }

                $data = [
                    'resFilename' => $file,

                    'resBrowserName' => $encodedData['browser']['name'] ?? null,
                    'resBrowserVersion' => $encodedData['browser']['version'] ?? null,

                    'resEngineName' => $encodedData['engine']['name'] ?? null,
                    'resEngineVersion' => $encodedData['engine']['version'] ?? null,

                    'resOsName' => $encodedData['os']['name'] ?? null,
                    'resOsVersion' => $encodedData['os']['version'] ?? null,

                    'resDeviceModel' => $encodedData['device']['deviceName'] ?? null,
                    'resDeviceBrand' => $encodedData['device']['brand'] ?? null,
                    'resDeviceType' => $encodedData['device']['type'] ?? null,
                    'resDeviceIsMobile' => null,
                    'resDeviceIsTouch' => null,

                    'resBotIsBot' => null,
                    'resBotName' => null,
                    'resBotType' => null
                ];

                $toInsert = [
                    'uaString' => $ua,
                    'result' => $data,
                ];

                if (count($encodedData['headers']) > 1) {
                    unset($encodedData['headers']['user-agent']);

                    $toInsert['uaAdditionalHeaders'] = $encodedData['headers'];

                    $key = bin2hex(sha1($ua . ' ' . json_encode($encodedData['headers']), true));
                } else {
                    $key = bin2hex(sha1($ua, true));
                }

                yield $key => $toInsert;
            }
        }
    }
}
