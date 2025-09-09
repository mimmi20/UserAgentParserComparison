<?php

/**
 * This file is part of the mimmi20/user-agent-parser-comparison package.
 *
 * Copyright (c) 2015-2025, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use Override;
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
    protected string $language    = 'PHP';

    /**
     * Set this in each Provider implementation
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    protected array $detectionCapabilities = [
        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => true,
        ],
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'brand' => true,
            'isMobile' => true,
            'isTouch' => true,
            'model' => true,
            'type' => true,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => true,
            'version' => false,
        ],
    ];

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'bot' => [
            'name' => [
                '/^Bot$/i',
                '/^Generic Bot$/i',
            ],
        ],
        'general' => ['/^UNK$/i'],
    ];

    /** @throws void */
    public function __construct(private readonly DeviceDetector $parser)
    {
        // nothing to do here
    }

    /** @throws void */
    #[Override]
    public function isActive(): bool
    {
        try {
            $this->checkIfInstalled();
        } catch (PackageNotLoadedException) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, string> $headers
     *
     * @throws NoResultFoundException
     */
    #[Override]
    public function parse(array $headers = []): Model\UserAgent
    {
        $clientHints = ClientHints::factory($headers);

        $this->parser->setUserAgent($headers['user-agent'] ?? '');
        $this->parser->setClientHints($clientHints);
        $this->parser->parse();

        /*
         * No result found?
         */
        if ($this->hasResult($this->parser) !== true) {
            throw new NoResultFoundException(
                'No result found for user agent: ' . ($headers['user-agent'] ?? ''),
            );
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($this->getResultRaw($this->parser));

        /*
         * Bot detection
         */
        if ($this->parser->isBot() === true) {
            $this->hydrateBot($result->getBot(), (array) $this->parser->getBot());

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), (array) $this->parser->getClient());
        $this->hydrateRenderingEngine(
            $result->getRenderingEngine(),
            (array) $this->parser->getClient(),
        );
        $this->hydrateOperatingSystem($result->getOperatingSystem(), (array) $this->parser->getOs());
        $this->hydrateDevice($result->getDevice(), $this->parser);

        return $result;
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return array{client: array<mixed>|string|null, operatingSystem: array<mixed>|string|null, device: array<string, mixed>, bot: array<mixed>|bool|null, extra: array<string, mixed>}
     *
     * @throws void
     */
    private function getResultRaw(DeviceDetector $dd): array
    {
        return [
            'bot' => $dd->getBot(),
            'client' => $dd->getClient(),

            'device' => [
                'brand' => $dd->getBrandName(),
                'brandName' => $dd->getBrandName(),

                'device' => $dd->getDevice(),
                'deviceName' => $dd->getDeviceName(),

                'model' => $dd->getModel(),
            ],

            'extra' => [
                'isBot' => $dd->isBot(),

                // client
                'isBrowser' => $dd->isBrowser(),

                // deviceType
                'isCamera' => $dd->isCamera(),
                'isCarBrowser' => $dd->isCarBrowser(),
                'isConsole' => $dd->isConsole(),

                // other special
                'isDesktop' => $dd->isDesktop(),
                'isFeaturePhone' => $dd->isFeaturePhone(),
                'isFeedReader' => $dd->isFeedReader(),
                'isLibrary' => $dd->isLibrary(),
                'isMediaPlayer' => $dd->isMediaPlayer(),
                'isMobile' => $dd->isMobile(),
                'isMobileApp' => $dd->isMobileApp(),
                'isPhablet' => $dd->isPhablet(),
                'isPIM' => $dd->isPIM(),
                'isPortableMediaPlayer' => $dd->isPortableMediaPlayer(),
                'isSmartDisplay' => $dd->isSmartDisplay(),
                'isSmartphone' => $dd->isSmartphone(),
                'isTablet' => $dd->isTablet(),
                'isTouchEnabled' => $dd->isTouchEnabled(),
                'isTV' => $dd->isTV(),
            ],
            'operatingSystem' => $dd->getOs(),
        ];
    }

    /** @throws void */
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

        return $dd->getDevice() !== null;
    }

    /**
     * @param array<string, string> $botRaw
     *
     * @throws void
     */
    private function hydrateBot(Model\Bot $bot, array $botRaw): void
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

    /**
     * @param array<string, string> $clientRaw
     *
     * @throws void
     */
    private function hydrateBrowser(Model\Browser $browser, array $clientRaw): void
    {
        if (isset($clientRaw['name'])) {
            $browser->setName($this->getRealResult($clientRaw['name']));
        }

        if (!isset($clientRaw['version'])) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($clientRaw['version']));
    }

    /**
     * @param array<string, string> $clientRaw
     *
     * @throws void
     */
    private function hydrateRenderingEngine(Model\RenderingEngine $engine, array $clientRaw): void
    {
        if (!isset($clientRaw['engine'])) {
            return;
        }

        $engine->setName($this->getRealResult($clientRaw['engine']));
    }

    /**
     * @param array<string, string> $osRaw
     *
     * @throws void
     */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, array $osRaw): void
    {
        if (isset($osRaw['name'])) {
            $os->setName($this->getRealResult($osRaw['name']));
        }

        if (!isset($osRaw['version'])) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($osRaw['version']));
    }

    /** @throws void */
    private function hydrateDevice(Model\Device $device, DeviceDetector $dd): void
    {
        $device->setModel($this->getRealResult($dd->getModel()));
        $device->setBrand($this->getRealResult($dd->getBrandName()));
        $device->setType($this->getRealResult($dd->getDeviceName()));
        $device->setIsMobile($dd->isMobile());
        $device->setIsTouch($dd->isTouchEnabled());
    }
}
