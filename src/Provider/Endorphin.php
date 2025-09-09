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

use EndorphinStudio\Detector as EndorphinDetector;
use EndorphinStudio\Detector\Data\Result;
use Override;
use Throwable;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

use function array_key_exists;
use function is_string;

/**
 * Abstraction for endorphin-studio/browser-detector
 *
 * @see https://github.com/EndorphinDetector-studio/browser-detector
 */
final class Endorphin extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'Endorphin';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/endorphin-studio/browser-detector';

    /**
     * Composer package name
     */
    protected string $packageName = 'endorphin-studio/browser-detector';
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
            'brand' => false,
            'isMobile' => false,
            'isTouch' => false,
            'model' => false,
            'type' => true,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => false,
            'version' => false,
        ],
    ];

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'device' => [
            'model' => ['/^Desktop/i'],
        ],
        'general' => ['/^N\\\A$/i'],
    ];

    /** @throws void */
    public function __construct(private readonly EndorphinDetector\Detector $parser)
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
        if (!array_key_exists('user-agent', $headers) || !is_string($headers['user-agent'])) {
            throw new NoResultFoundException('Can only use the user-agent Header');
        }

        try {
            $resultRaw = $this->parser->analyse($headers['user-agent']);
        } catch (Throwable $e) {
            throw new NoResultFoundException(
                'No result found for user agent: ' . $headers['user-agent'],
                0,
                $e,
            );
        }

        /*
         * No result found?
         */
        if ($this->hasResult($resultRaw) !== true) {
            throw new NoResultFoundException(
                'No result found for user agent: ' . $headers['user-agent'],
            );
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($resultRaw);

        /*
         * Bot detection
         */
        if ($this->isRealResult($resultRaw->getRobot()->getType()) === true) {
            $this->hydrateBot($result->getBot(), $resultRaw->getRobot());

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw->getBrowser());

        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw->getOs());

        $this->hydrateDevice($result->getDevice(), $resultRaw->getDevice());

        return $result;
    }

    /** @throws void */
    private function hasResult(Result $resultRaw): bool
    {
        if ($this->isRealResult($resultRaw->getOs()->getName()) === true) {
            return true;
        }

        if ($this->isRealResult($resultRaw->getBrowser()->getName()) === true) {
            return true;
        }

        if ($this->isRealResult($resultRaw->getDevice()->getName(), 'device', 'model') === true) {
            return true;
        }

        return $this->isRealResult($resultRaw->getRobot()->getType()) === true;
    }

    /** @throws void */
    private function hydrateBot(Model\Bot $bot, EndorphinDetector\Data\Robot $resultRaw): void
    {
        $bot->setIsBot(true);
        $bot->setName($this->getRealResult($resultRaw->getName()));
        $bot->setType($this->getRealResult($resultRaw->getType()));
    }

    /** @throws void */
    private function hydrateBrowser(Model\Browser $browser, EndorphinDetector\Data\Browser $resultRaw): void
    {
        $browser->setName($this->getRealResult($resultRaw->getName()));
        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->getVersion()));
    }

    /** @throws void */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, EndorphinDetector\Data\Os $resultRaw): void
    {
        $os->setName($this->getRealResult($resultRaw->getName()));
        $os->getVersion()->setComplete($this->getRealResult($resultRaw->getVersion()));
    }

    /** @throws void */
    private function hydrateDevice(Model\Device $device, EndorphinDetector\Data\Device $resultRaw): void
    {
        $device->setModel(
            $this->getRealResult($resultRaw->getModel()->getModel()),
        );
        $device->setType($this->getRealResult($resultRaw->getType()));
    }
}
