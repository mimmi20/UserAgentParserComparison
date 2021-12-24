<?php
namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;
use DeviceDetector\Parser\Client\Browser;
use DeviceDetector\Parser\Device\AbstractDeviceParser;

/**
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 * @see https://github.com/browscap/browscap-php
 */
class Matomo extends AbstractTestProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected string $name = 'MatomoDeviceDetector';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected string $homepage = 'https://github.com/matomo/device-detector';

    /**
     * Composer package name
     *
     * @var string
     */
    protected string $packageName = 'matomo/device-detector';

    protected string $language = 'PHP';

    protected array $detectionCapabilities = [

        'browser' => [
            'name'    => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name'    => true,
            'version' => false,
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
        $path = 'vendor/matomo/device-detector/Tests/fixtures';

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $files = new class($iterator, 'yml') extends \FilterIterator {
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

            $file = $file[0];

            $provider = \Spyc::YAMLLoad($file);

            foreach ($provider as $data) {
                // If no client property, may be in bot file, which we're not parsing just yet

                if (!array_key_exists('client', $data) || !array_key_exists('user_agent', $data)) {
                    continue;
                }

                $ua = $data['user_agent'];

                if ('' === $ua) {
                    continue;
                }

                $data = [
                    'resFilename' => $file,

                    'resBrowserName' => $data['client']['name'] ?? null,
                    'resBrowserVersion' => $data['client']['version'] ?? null,

                    'resEngineName' => $data['client']['engine'] ?? null,
                    'resEngineVersion' => $data['client']['engine_version'] ?? null,

                    'resOsName' => $data['os']['name'] ?? null,
                    'resOsVersion' => $data['os']['version'] ?? null,

                    'resDeviceModel' => $data['device']['model'] ?? null,
                    'resDeviceBrand' => AbstractDeviceParser::getFullName($data['device']['brand']),
                    'resDeviceType' => $data['device']['type'] ?? null,
                    'resDeviceIsMobile' => (int) $this->isMobile($data),
                    'resDeviceIsTouch' => null,

                    'resBotIsBot' => null,
                    'resBotName' => null,
                    'resBotType' => null
                ];

                $key = bin2hex(sha1($ua, true));
                $toInsert = [
                    'uaString' => $ua,
                    'result' => $data,
                ];

                yield $key => $toInsert;
            }
        }
    }

    // These functions are adapted from DeviceDetector's source
    // Didn't want to use the actual classes here due to performance and consideration of what we're actually testing
    // (i.e. how can the parser ever fail on this field if the parser is generating it)
    private function isMobile(array $data): bool
    {
        $device     = $data['device']['type'];
        $deviceType = AbstractDeviceParser::getAvailableDeviceTypes()[$device] ?? null;

        // Mobile device types
        if (!empty($deviceType) && in_array($deviceType, [
                AbstractDeviceParser::DEVICE_TYPE_FEATURE_PHONE,
                AbstractDeviceParser::DEVICE_TYPE_SMARTPHONE,
                AbstractDeviceParser::DEVICE_TYPE_TABLET,
                AbstractDeviceParser::DEVICE_TYPE_PHABLET,
                AbstractDeviceParser::DEVICE_TYPE_CAMERA,
                AbstractDeviceParser::DEVICE_TYPE_PORTABLE_MEDIA_PAYER,
            ], true)
        ) {
            return true;
        }

        // non mobile device types
        if (!empty($deviceType) && in_array($deviceType, [
                AbstractDeviceParser::DEVICE_TYPE_TV,
                AbstractDeviceParser::DEVICE_TYPE_SMART_DISPLAY,
                AbstractDeviceParser::DEVICE_TYPE_CONSOLE,
            ], true)
        ) {
            return false;
        }

        // Check for browsers available for mobile devices only
        if (isset($data['client']['type'])
            && $data['client']['type'] === 'browser'
            && Browser::isMobileOnlyBrowser($data['client']['short_name'] ?? 'UNK')
        ) {
            return true;
        }

        return !$this->isDesktop($data);
    }

    private function isDesktop(array $data): bool
    {
        // Check for browsers available for mobile devices only
        if (isset($data['client']['type'])
            && $data['client']['type'] === 'browser'
            && Browser::isMobileOnlyBrowser($data['client']['short_name'] ?? 'UNK')
        ) {
            return false;
        }

        return in_array($data['os_family'], ['AmigaOS', 'IBM', 'GNU/Linux', 'Mac', 'Unix', 'Windows', 'BeOS', 'Chrome OS'], true);
    }
}
