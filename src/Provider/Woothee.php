<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use Throwable;
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

    /** @throws PackageNotLoadedException */
    public function __construct(private readonly Classifier $parser)
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
        try {
            $resultRaw = $this->parser->parse($userAgent);
        } catch (Throwable $e) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent, 0, $e);
        }

        /*
         * No result found?
         */
        if ($this->hasResult($resultRaw) !== true) {
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

    /** @throws void */
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

    /** @throws void */
    private function isBot(array $resultRaw): bool
    {
        return isset($resultRaw['category']) && $resultRaw['category'] === DataSet::DATASET_CATEGORY_CRAWLER;
    }

    /** @throws void */
    private function hydrateBot(Model\Bot $bot, array $resultRaw): void
    {
        $bot->setIsBot(true);

        if (!isset($resultRaw['name'])) {
            return;
        }

        $bot->setName($this->getRealResult($resultRaw['name'], 'bot', 'name'));
    }

    /** @throws void */
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

    /** @throws void */
    private function hydrateDevice(Model\Device $device, array $resultRaw): void
    {
        if (!isset($resultRaw['category'])) {
            return;
        }

        $device->setType($this->getRealResult($resultRaw['category'], 'device', 'type'));
    }
}
