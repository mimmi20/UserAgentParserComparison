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
use Throwable;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;
use Woothee\Classifier;
use Woothee\DataSet;

use function array_key_exists;
use function is_string;

/**
 * Abstraction for woothee/woothee
 *
 * @see https://github.com/woothee/woothee-php
 */
final class Woothee extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'Woothee';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/woothee/woothee-php';

    /**
     * Composer package name
     */
    protected string $packageName = 'woothee/woothee';
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
            'brand' => false,
            'isMobile' => false,
            'isTouch' => false,
            'model' => false,
            'type' => true,
        ],

        'operatingSystem' => [
            'name' => false,
            'version' => false,
        ],

        'renderingEngine' => [
            'name' => false,
            'version' => false,
        ],
    ];

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'bot' => [
            'name' => ['/^misc crawler$/i'],
        ],

        'device' => [
            'type' => ['/^misc$/i'],
        ],
        'general' => ['/^UNKNOWN$/i'],
    ];

    /** @throws void */
    public function __construct(private readonly Classifier $parser)
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
            $resultRaw = $this->parser->parse($headers['user-agent']);
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
        if ($resultRaw === false || $this->hasResult($resultRaw) !== true) {
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
        if ($this->isBot($resultRaw) === true) {
            $this->hydrateBot($result->getBot(), $resultRaw);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw);
        // renderingEngine not available
        // operatingSystem filled OS is mixed! Examples: iPod, iPhone, Android...
        $this->hydrateDevice($result->getDevice(), $resultRaw);

        return $result;
    }

    /**
     * @param array<string, string|null> $resultRaw
     *
     * @throws void
     */
    private function hasResult(array $resultRaw): bool
    {
        if (
            isset($resultRaw['category'])
            && $this->isRealResult($resultRaw['category'], 'device', 'type')
        ) {
            return true;
        }

        return isset($resultRaw['name']) && $this->isRealResult($resultRaw['name']);
    }

    /**
     * @param array<string, string|null> $resultRaw
     *
     * @throws void
     */
    private function isBot(array $resultRaw): bool
    {
        return isset($resultRaw['category']) && $resultRaw['category'] === DataSet::DATASET_CATEGORY_CRAWLER;
    }

    /**
     * @param array<string, string|null> $resultRaw
     *
     * @throws void
     */
    private function hydrateBot(Model\Bot $bot, array $resultRaw): void
    {
        $bot->setIsBot(true);

        if (!isset($resultRaw['name'])) {
            return;
        }

        $bot->setName($this->getRealResult($resultRaw['name'], 'bot', 'name'));
    }

    /**
     * @param array<string, string|null> $resultRaw
     *
     * @throws void
     */
    private function hydrateBrowser(Model\Browser $browser, array $resultRaw): void
    {
        if (isset($resultRaw['name'])) {
            $browser->setName($this->getRealResult($resultRaw['name']));
        }

        if (!isset($resultRaw['version'])) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($resultRaw['version']));
    }

    /**
     * @param array<string, string|null> $resultRaw
     *
     * @throws void
     */
    private function hydrateDevice(Model\Device $device, array $resultRaw): void
    {
        if (!isset($resultRaw['category'])) {
            return;
        }

        $device->setType($this->getRealResult($resultRaw['category'], 'device', 'type'));
    }
}
