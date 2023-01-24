<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use DeviceDetector\ClientHints;
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

    /** @var array<string, array<int|string, array<mixed>|string>> */
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
    public function __construct(private readonly DeviceDetector $parser)
    {
        $this->checkIfInstalled();
    }

    /**
     * @throws NoResultFoundException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $clientHints = ClientHints::factory($headers);

        $this->parser->setUserAgent($userAgent);
        $this->parser->setClientHints($clientHints);
        $this->parser->parse();

        /*
         * No result found?
         */
        if (true !== $this->hasResult($this->parser)) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($this->getResultRaw($this->parser));

        /*
         * Bot detection
         */
        if (true === $this->parser->isBot()) {
            $this->hydrateBot($result->getBot(), $this->parser->getBot());

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $this->parser->getClient());
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $this->parser->getClient());
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $this->parser->getOs());
        $this->hydrateDevice($result->getDevice(), $this->parser);

        return $result;
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return array{client: array<mixed>|string|null, operatingSystem: array<mixed>|string|null, device: array<string, mixed>, bot: array<mixed>|bool|null, extra: array<string, mixed>}
     */
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
        $device->setIsMobile($dd->isMobile());
        $device->setIsTouch($dd->isTouchEnabled());
    }
}
