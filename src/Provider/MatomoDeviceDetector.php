<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use DeviceDetector\DeviceDetector;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for matomo/device-detector
 *
 * @see https://github.com/matomo/device-detector
 */
final class MatomoDeviceDetector extends AbstractParseProvider
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

    protected array $defaultValues = [
        'general' => ['/^UNK$/i'],

        'bot' => [
            'name' => [
                '/^Bot$/i',
                '/^Generic Bot$/i',
            ],
        ],
    ];

    /** @throws PackageNotLoadedException */
    public function __construct(private DeviceDetector | null $parser = null)
    {
        if (null !== $parser) {
            return;
        }

        $this->checkIfInstalled();
    }

    public function getParser(): DeviceDetector
    {
        if (null !== $this->parser) {
            return $this->parser;
        }

        $this->parser = new DeviceDetector();

        return $this->parser;
    }

    /**
     * @throws NoResultFoundException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $dd = $this->getParser();

        $dd->setUserAgent($userAgent);
        $dd->parse();

        /*
         * No result found?
         */
        if (true !== $this->hasResult($dd)) {
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
        if (true === $dd->isBot()) {
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

    /** @return array */
    private function getResultRaw(DeviceDetector $dd): array
    {
        return [
            'client' => $dd->getClient(),
            'operatingSystem' => $dd->getOs(),

            'device' => [
                'brand' => $dd->getBrand(),
                'brandName' => $dd->getBrandName(),

                'model' => $dd->getModel(),

                'device' => $dd->getDevice(),
                'deviceName' => $dd->getDeviceName(),
            ],

            'bot' => $dd->getBot(),

            'extra' => [
                'isBot' => $dd->isBot(),

                // client
                'isBrowser' => $dd->isBrowser(),
                'isFeedReader' => $dd->isFeedReader(),
                'isMobileApp' => $dd->isMobileApp(),
                'isPIM' => $dd->isPIM(),
                'isLibrary' => $dd->isLibrary(),
                'isMediaPlayer' => $dd->isMediaPlayer(),

                // deviceType
                'isCamera' => $dd->isCamera(),
                'isCarBrowser' => $dd->isCarBrowser(),
                'isConsole' => $dd->isConsole(),
                'isFeaturePhone' => $dd->isFeaturePhone(),
                'isPhablet' => $dd->isPhablet(),
                'isPortableMediaPlayer' => $dd->isPortableMediaPlayer(),
                'isSmartDisplay' => $dd->isSmartDisplay(),
                'isSmartphone' => $dd->isSmartphone(),
                'isTablet' => $dd->isTablet(),
                'isTV' => $dd->isTV(),

                // other special
                'isDesktop' => $dd->isDesktop(),
                'isMobile' => $dd->isMobile(),
                'isTouchEnabled' => $dd->isTouchEnabled(),
            ],
        ];
    }

    private function hasResult(DeviceDetector $dd): bool
    {
        if (true === $dd->isBot()) {
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

        return null !== $dd->getDevice();
    }

    /** @param array|bool $botRaw */
    private function hydrateBot(Model\Bot $bot, $botRaw): void
    {
        $bot->setIsBot(true);

        if (isset($botRaw['name'])) {
            $bot->setName($this->getRealResult($botRaw['name'], 'bot', 'name'));
        }

        if (!isset($botRaw['category'])) {
            return;
        }

        $bot->setType($this->getRealResult($botRaw['category']));
    }

    /** @param array|string $clientRaw */
    private function hydrateBrowser(Model\Browser $browser, $clientRaw): void
    {
        if (isset($clientRaw['name'])) {
            $browser->setName($this->getRealResult($clientRaw['name']));
        }

        if (!isset($clientRaw['version'])) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($clientRaw['version']));
    }

    /** @param array|string $clientRaw */
    private function hydrateRenderingEngine(Model\RenderingEngine $engine, $clientRaw): void
    {
        if (!isset($clientRaw['engine'])) {
            return;
        }

        $engine->setName($this->getRealResult($clientRaw['engine']));
    }

    /** @param array|string $osRaw */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, $osRaw): void
    {
        if (isset($osRaw['name'])) {
            $os->setName($this->getRealResult($osRaw['name']));
        }

        if (!isset($osRaw['version'])) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($osRaw['version']));
    }

    private function hydrateDevice(Model\Device $device, DeviceDetector $dd): void
    {
        $device->setModel($this->getRealResult($dd->getModel()));
        $device->setBrand($this->getRealResult($dd->getBrandName()));
        $device->setType($this->getRealResult($dd->getDeviceName()));

        if (true === $dd->isMobile()) {
            $device->setIsMobile(true);
        }

        if (true !== $dd->isTouchEnabled()) {
            return;
        }

        $device->setIsTouch(true);
    }
}
