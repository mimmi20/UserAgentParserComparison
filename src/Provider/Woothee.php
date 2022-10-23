<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;
use Woothee\Classifier;
use Woothee\DataSet;

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
            'name' => false,
            'version' => false,
        ],

        'operatingSystem' => [
            'name' => false,
            'version' => false,
        ],

        'device' => [
            'model' => false,
            'brand' => false,
            'type' => true,
            'isMobile' => false,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => false,
        ],
    ];

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'general' => ['/^UNKNOWN$/i'],

        'device' => [
            'type' => ['/^misc$/i'],
        ],

        'bot' => [
            'name' => ['/^misc crawler$/i'],
        ],
    ];

    private Classifier | null $parser = null;

    /** @throws PackageNotLoadedException */
    public function __construct()
    {
        $this->checkIfInstalled();
    }

    public function getParser(): Classifier
    {
        if (null !== $this->parser) {
            return $this->parser;
        }

        $this->parser = new Classifier();

        return $this->parser;
    }

    /**
     * @throws NoResultFoundException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $parser = $this->getParser();

        $resultRaw = $parser->parse($userAgent);

        /*
         * No result found?
         */
        if (true !== $this->hasResult($resultRaw)) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($resultRaw);

        /*
         * Bot detection
         */
        if (true === $this->isBot($resultRaw)) {
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

    private function hasResult(array $resultRaw): bool
    {
        if (isset($resultRaw['category']) && $this->isRealResult($resultRaw['category'], 'device', 'type')) {
            return true;
        }

        return isset($resultRaw['name']) && $this->isRealResult($resultRaw['name']);
    }

    private function isBot(array $resultRaw): bool
    {
        return isset($resultRaw['category']) && DataSet::DATASET_CATEGORY_CRAWLER === $resultRaw['category'];
    }

    private function hydrateBot(Model\Bot $bot, array $resultRaw): void
    {
        $bot->setIsBot(true);

        if (!isset($resultRaw['name'])) {
            return;
        }

        $bot->setName($this->getRealResult($resultRaw['name'], 'bot', 'name'));
    }

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

    private function hydrateDevice(Model\Device $device, array $resultRaw): void
    {
        if (!isset($resultRaw['category'])) {
            return;
        }

        $device->setType($this->getRealResult($resultRaw['category'], 'device', 'type'));
    }
}
