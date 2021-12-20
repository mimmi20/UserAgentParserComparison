<?php
namespace UserAgentParserComparison\Provider;

use DeviceDetector\DeviceDetector;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for matomo/device-detector
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 * @see https://github.com/matomo/device-detector
 */
class MatomoDeviceDetector extends AbstractParseProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected $name = 'MatomoDeviceDetector';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected $homepage = 'https://github.com/matomo/device-detector';

    /**
     * Composer package name
     *
     * @var string
     */
    protected $packageName = 'matomo/device-detector';

    protected $detectionCapabilities = [

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

    protected $defaultValues = [

        'general' => [
            '/^UNK$/i',
        ],

        'bot' => [
            'name' => [
                '/^Bot$/i',
                '/^Generic Bot$/i',
            ],
        ],
    ];

    /**
     *
     * @var DeviceDetector
     */
    private $parser;

    /**
     *
     * @param  DeviceDetector|null            $parser
     * @throws PackageNotLoadedException
     */
    public function __construct(DeviceDetector $parser = null)
    {
        if ($parser === null) {
            $this->checkIfInstalled();
        }

        $this->parser = $parser;
    }

    /**
     *
     * @return DeviceDetector
     */
    public function getParser(): DeviceDetector
    {
        if ($this->parser !== null) {
            return $this->parser;
        }

        $this->parser = new DeviceDetector();

        return $this->parser;
    }

    /**
     *
     * @param DeviceDetector $dd
     *
     * @return array
     */
    private function getResultRaw(DeviceDetector $dd): array
    {
        $raw = [
            'client'          => $dd->getClient(),
            'operatingSystem' => $dd->getOs(),

            'device' => [
                'brand'     => $dd->getBrand(),
                'brandName' => $dd->getBrandName(),

                'model' => $dd->getModel(),

                'device'     => $dd->getDevice(),
                'deviceName' => $dd->getDeviceName(),
            ],

            'bot' => $dd->getBot(),

            'extra' => [
                'isBot' => $dd->isBot(),

                // client
                'isBrowser'     => $dd->isBrowser(),
                'isFeedReader'  => $dd->isFeedReader(),
                'isMobileApp'   => $dd->isMobileApp(),
                'isPIM'         => $dd->isPIM(),
                'isLibrary'     => $dd->isLibrary(),
                'isMediaPlayer' => $dd->isMediaPlayer(),

                // deviceType
                'isCamera'              => $dd->isCamera(),
                'isCarBrowser'          => $dd->isCarBrowser(),
                'isConsole'             => $dd->isConsole(),
                'isFeaturePhone'        => $dd->isFeaturePhone(),
                'isPhablet'             => $dd->isPhablet(),
                'isPortableMediaPlayer' => $dd->isPortableMediaPlayer(),
                'isSmartDisplay'        => $dd->isSmartDisplay(),
                'isSmartphone'          => $dd->isSmartphone(),
                'isTablet'              => $dd->isTablet(),
                'isTV'                  => $dd->isTV(),

                // other special
                'isDesktop'      => $dd->isDesktop(),
                'isMobile'       => $dd->isMobile(),
                'isTouchEnabled' => $dd->isTouchEnabled(),
            ],
        ];

        return $raw;
    }

    /**
     *
     * @param DeviceDetector $dd
     *
     * @return bool
     */
    private function hasResult(DeviceDetector $dd): bool
    {
        if ($dd->isBot() === true) {
            return true;
        }

        $client = $dd->getClient();
        if (isset($client['name']) && $this->isRealResult($client['name'])) {
            return true;
        }

        $os = $dd->getOs();
        if (isset($os['name']) && $this->isRealResult($os['name'])) {
            return true;
        }

        if ($dd->getDevice() !== null) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param Model\Bot     $bot
     * @param array|boolean $botRaw
     */
    private function hydrateBot(Model\Bot $bot, $botRaw): void
    {
        $bot->setIsBot(true);

        if (isset($botRaw['name'])) {
            $bot->setName($this->getRealResult($botRaw['name'], 'bot', 'name'));
        }
        if (isset($botRaw['category'])) {
            $bot->setType($this->getRealResult($botRaw['category']));
        }
    }

    /**
     *
     * @param Model\Browser $browser
     * @param array|string  $clientRaw
     */
    private function hydrateBrowser(Model\Browser $browser, $clientRaw): void
    {
        if (isset($clientRaw['name'])) {
            $browser->setName($this->getRealResult($clientRaw['name']));
        }

        if (isset($clientRaw['version'])) {
            $browser->getVersion()->setComplete($this->getRealResult($clientRaw['version']));
        }
    }

    /**
     *
     * @param Model\RenderingEngine $engine
     * @param array|string          $clientRaw
     */
    private function hydrateRenderingEngine(Model\RenderingEngine $engine, $clientRaw): void
    {
        if (isset($clientRaw['engine'])) {
            $engine->setName($this->getRealResult($clientRaw['engine']));
        }
    }

    /**
     *
     * @param Model\OperatingSystem $os
     * @param array|string          $osRaw
     */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, $osRaw): void
    {
        if (isset($osRaw['name'])) {
            $os->setName($this->getRealResult($osRaw['name']));
        }

        if (isset($osRaw['version'])) {
            $os->getVersion()->setComplete($this->getRealResult($osRaw['version']));
        }
    }

    /**
     *
     * @param Model\Device $device
     * @param DeviceDetector  $dd
     */
    private function hydrateDevice(Model\Device $device, DeviceDetector $dd): void
    {
        $device->setModel($this->getRealResult($dd->getModel()));
        $device->setBrand($this->getRealResult($dd->getBrandName()));
        $device->setType($this->getRealResult($dd->getDeviceName()));

        if ($dd->isMobile() === true) {
            $device->setIsMobile(true);
        }

        if ($dd->isTouchEnabled() === true) {
            $device->setIsTouch(true);
        }
    }

    public function parse($userAgent, array $headers = []): Model\UserAgent
    {
        $dd = $this->getParser();

        $dd->setUserAgent($userAgent);
        $dd->parse();

        /*
         * No result found?
         */
        if ($this->hasResult($dd) !== true) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($this->getResultRaw($dd));

        /*
         * Bot detection
         */
        if ($dd->isBot() === true) {
            $this->hydrateBot($result->getBot(), $dd->getBot());

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $dd->getClient());
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $dd->getClient());
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $dd->getOs());
        $this->hydrateDevice($result->getDevice(), $dd);

        return $result;
    }
}
