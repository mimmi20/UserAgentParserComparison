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

use BrowserDetector\Detector;
use Override;
use Psr\SimpleCache\InvalidArgumentException;
use UaResult\Browser\BrowserInterface;
use UaResult\Device\DeviceInterface;
use UaResult\Engine\EngineInterface;
use UaResult\Os\OsInterface;
use UaResult\Result\ResultInterface;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Model;

use function mb_stripos;

/**
 * Abstraction for mimmi20/BrowserDetector
 *
 * @see https://github.com/mimmi20/browser-detector
 */
final class BrowserDetector extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'BrowserDetector (mimmi20)';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/mimmi20/browser-detector';

    /**
     * Composer package name
     */
    protected string $packageName = 'mimmi20/browser-detector';
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
            'isTouch' => false,
            'model' => true,
            'type' => true,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => true,
            'version' => true,
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
    public function __construct(private readonly Detector $parser)
    {
    }

    /** @throws void */
    #[Override]
    public function isActive(): bool
    {
        return false;

//        try {
//            $this->checkIfInstalled();
//        } catch (PackageNotLoadedException) {
//            return false;
//        }
//
//        return true;
    }

    /**
     * @param array<string, string> $headers
     *
     * @throws NoResultFoundException
     * @throws InvalidArgumentException
     */
    #[Override]
    public function parse(array $headers = []): Model\UserAgent
    {
        $parserResult = $this->parser->getBrowser($headers);

        /*
         * No result found?
         */
        if ($this->hasResult($parserResult) !== true) {
            throw new NoResultFoundException(
                'No result found for user agent: ' . ($headers['user-agent'] ?? ''),
            );
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($this->getResultRaw($parserResult));

        /*
         * Bot detection
         */
        if ($result->isBot() === true) {
            $this->hydrateBot($result->getBot(), $parserResult->getBrowser()->getType()->isBot());

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $parserResult->getBrowser());
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $parserResult->getEngine());
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $parserResult->getOs());
        $this->hydrateDevice($result->getDevice(), $parserResult->getDevice());

        return $result;
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return array{client: array<mixed>|string|null, operatingSystem: array<mixed>|string|null, device: array<string, mixed>, bot: array<mixed>|bool|null, extra: array<string, mixed>}
     *
     * @throws void
     */
    private function getResultRaw(ResultInterface $result): array
    {
        return [
            'bot' => null,
            'client' => $result->getBrowser()->getName(),

            'device' => [
                'brand' => $result->getDevice()->getBrand()->getName(),
                'brandName' => $result->getDevice()->getBrand()->getBrandName(),

                'device' => $result->getDevice()->getDeviceName(),
                'deviceName' => $result->getDevice()->getMarketingName(),

                'model' => $result->getDevice()->getMarketingName(),
            ],

            'extra' => [
                'isBot' => $result->getBrowser()->getType()->isBot(),
            ],
            'operatingSystem' => $result->getOs()->getName(),
        ];
    }

    /** @throws void */
    private function hasResult(ResultInterface $result): bool
    {
        if ($result->getBrowser()->getType()->isBot()) {
            return true;
        }

        $client = $result->getBrowser()->getName();

        if ($client !== null && $this->isRealResult($client)) {
            return true;
        }

        $os = $result->getOs()->getName();

        if ($os !== null && $this->isRealResult($os)) {
            return true;
        }

        $engine = $result->getEngine()->getName();

        if ($engine !== null && $this->isRealResult($engine)) {
            return true;
        }

        $device = $result->getDevice()->getDeviceName();

        return $device !== null && mb_stripos($device, 'general') === false && $this->isRealResult(
            $device,
        );
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

    /** @throws void */
    private function hydrateBrowser(Model\Browser $browser, BrowserInterface $clientRaw): void
    {
        if ($clientRaw->getName()) {
            $browser->setName($this->getRealResult($clientRaw->getName()));
        }

        if (!$clientRaw->getVersion()) {
            return;
        }

        $browser->getVersion()->setComplete(
            $this->getRealResult($clientRaw->getVersion()->getVersion()),
        );
    }

    /** @throws void */
    private function hydrateRenderingEngine(Model\RenderingEngine $engine, EngineInterface $clientRaw): void
    {
        if (!$clientRaw->getName()) {
            return;
        }

        $engine->setName($this->getRealResult($clientRaw->getName()));
    }

    /** @throws void */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, OsInterface $osRaw): void
    {
        if ($osRaw->getName()) {
            $os->setName($this->getRealResult($osRaw->getName()));
        }

        if (!$osRaw->getVersion()) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($osRaw->getVersion()->getVersion()));
    }

    /** @throws void */
    private function hydrateDevice(Model\Device $device, DeviceInterface $result): void
    {
        $deviceName = $result->getDeviceName();

        if ($deviceName !== null && mb_stripos($deviceName, 'general') !== false) {
            $device->setModel($this->getRealResult($deviceName));
        }

        $device->setBrand($this->getRealResult($result->getBrand()->getName()));
        $device->setType($this->getRealResult($result->getType()->getName()));

        if ($result->getType()->isMobile() === true) {
            $device->setIsMobile(true);
        }

        if ($result->getDisplay()->hasTouch() !== true) {
            return;
        }

        $device->setIsTouch(true);
    }
}
