<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use DeviceDetector\Parser\Client\Browser;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use FilterIterator;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Spyc;
use UserAgentParserComparison\Exception\NoResultFoundException;

use function array_key_exists;
use function assert;
use function bin2hex;
use function in_array;
use function sha1;

/** @see https://github.com/browscap/browscap-php */
final class Matomo extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'MatomoDeviceDetector';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/matomo/device-detector';

    /**
     * Composer package name
     */
    protected string $packageName = 'matomo/device-detector';

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
            'version' => false,
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
        $path = 'vendor/matomo/device-detector/Tests/fixtures';

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $files    = new class ($iterator, 'yml') extends FilterIterator {
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

            $provider = Spyc::YAMLLoad($file);

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
                    'resBotType' => null,
                ];

                $key      = bin2hex(sha1($ua, true));
                $toInsert = [
                    'uaString' => $ua,
                    'result' => $data,
                ];

                yield $key => $toInsert;
            }
        }
    }

    /**
     * These functions are adapted from DeviceDetector's source
     * Didn't want to use the actual classes here due to performance and consideration of what we're actually testing
     * (i.e. how can the parser ever fail on this field if the parser is generating it)
     */
    private function isMobile(array $data): bool
    {
        $device     = $data['device']['type'];
        $deviceType = AbstractDeviceParser::getAvailableDeviceTypes()[$device] ?? null;

        // Mobile device types
        if (
            !empty($deviceType) && in_array($deviceType, [
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
        if (
            !empty($deviceType) && in_array($deviceType, [
                AbstractDeviceParser::DEVICE_TYPE_TV,
                AbstractDeviceParser::DEVICE_TYPE_SMART_DISPLAY,
                AbstractDeviceParser::DEVICE_TYPE_CONSOLE,
            ], true)
        ) {
            return false;
        }

        // Check for browsers available for mobile devices only
        if (
            isset($data['client']['type'])
            && 'browser' === $data['client']['type']
            && Browser::isMobileOnlyBrowser($data['client']['short_name'] ?? 'UNK')
        ) {
            return true;
        }

        return !$this->isDesktop($data);
    }

    private function isDesktop(array $data): bool
    {
        // Check for browsers available for mobile devices only
        if (
            isset($data['client']['type'])
            && 'browser' === $data['client']['type']
            && Browser::isMobileOnlyBrowser($data['client']['short_name'] ?? 'UNK')
        ) {
            return false;
        }

        return in_array($data['os_family'], ['AmigaOS', 'IBM', 'GNU/Linux', 'Mac', 'Unix', 'Windows', 'BeOS', 'Chrome OS'], true);
    }
}
