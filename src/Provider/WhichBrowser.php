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

use Override;
use Psr\Cache\CacheItemPoolInterface;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;
use WhichBrowser\Model\Browser;
use WhichBrowser\Model\Device;
use WhichBrowser\Model\Engine;
use WhichBrowser\Model\Os;
use WhichBrowser\Model\Using;
use WhichBrowser\Parser as WhichBrowserParser;

/**
 * Abstraction for whichbrowser/parser
 *
 * @see https://github.com/WhichBrowser/Parser
 */
final class WhichBrowser extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'WhichBrowser';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/WhichBrowser/Parser';

    /**
     * Composer package name
     */
    protected string $packageName = 'whichbrowser/parser';
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
            'type' => false,
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

    /** @throws void */
    public function __construct(
        private readonly WhichBrowserParser $parser,
        private readonly CacheItemPoolInterface $cache,
    ) {
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
        $this->parser->analyse($headers, ['cache' => $this->cache]);

        /*
         * No result found?
         */
        if ($this->parser->isDetected() !== true) {
            throw new NoResultFoundException(
                'No result found for user agent: ' . ($headers['user-agent'] ?? ''),
            );
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($this->parser->toArray());

        /*
         * Bot detection
         */
        if ($this->parser->getType() === 'bot') {
            $this->hydrateBot($result->getBot(), $this->parser->browser);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $this->parser->browser);
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $this->parser->engine);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $this->parser->os);
        $this->hydrateDevice($result->getDevice(), $this->parser->device, $this->parser);

        return $result;
    }

    /** @throws void */
    private function hydrateBot(Model\Bot $bot, Browser $browserRaw): void
    {
        $bot->setIsBot(true);
        $bot->setName($this->getRealResult($browserRaw->getName()));
    }

    /** @throws void */
    private function hydrateBrowser(Model\Browser $browser, Browser $browserRaw): void
    {
        if ($this->isRealResult($browserRaw->getName(), 'browser', 'name') === true) {
            $browser->setName($browserRaw->getName());
            $browser->getVersion()->setComplete($this->getRealResult($browserRaw->getVersion()));

            return;
        }

        if (!$browserRaw->using instanceof Using) {
            return;
        }

        $usingRaw = $browserRaw->using;

        if ($this->isRealResult($usingRaw->getName()) !== true) {
            return;
        }

        $browser->setName($usingRaw->getName());

        $browser->getVersion()->setComplete($this->getRealResult($usingRaw->getVersion()));
    }

    /** @throws void */
    private function hydrateRenderingEngine(Model\RenderingEngine $engine, Engine $engineRaw): void
    {
        $engine->setName($this->getRealResult($engineRaw->getName()));
        $engine->getVersion()->setComplete($this->getRealResult($engineRaw->getVersion()));
    }

    /** @throws void */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, Os $osRaw): void
    {
        $os->setName($this->getRealResult($osRaw->getName()));
        $os->getVersion()->setComplete($this->getRealResult($osRaw->getVersion()));
    }

    /** @throws void */
    private function hydrateDevice(Model\Device $device, Device $deviceRaw, WhichBrowserParser $parser): void
    {
        $device->setModel($this->getRealResult($deviceRaw->getModel()));
        $device->setBrand($this->getRealResult($deviceRaw->getManufacturer()));
        $device->setType($this->getRealResult($parser->getType()));

        if ($parser->isMobile() !== true) {
            return;
        }

        $device->setIsMobile(true);
    }
}
