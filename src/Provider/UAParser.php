<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use UAParser\Parser;
use UAParser\Result\Client;
use UAParser\Result\Device;
use UAParser\Result\OperatingSystem;
use UAParser\Result\UserAgent;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

use function assert;

/**
 * Abstraction for ua-parser/uap-php
 *
 * @see https://github.com/ua-parser/uap-php
 */
final class UAParser extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'UAParser';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/ua-parser/uap-php';

    /**
     * Composer package name
     */
    protected string $packageName = 'ua-parser/uap-php';
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
            'isMobile' => false,
            'isTouch' => false,
            'model' => true,
            'type' => false,
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
        'bot' => [
            'name' => [
                '/^Other$/i',
                '/^crawler$/i',
                '/^robot$/i',
                '/^crawl$/i',
                '/^Spider$/i',
            ],
        ],

        'device' => [
            'brand' => [
                '/^Generic/i',
                '/^unknown$/i',
            ],

            'model' => [
                '/^generic$/i',
                '/^Smartphone$/i',
                '/^Feature Phone$/i',
                '/^iOS-Device$/i',
                '/^Tablet$/i',
                '/^Touch$/i',
                '/^Windows$/i',
                '/^Windows Phone$/i',
                '/^Android$/i',
            ],
        ],
        'general' => ['/^Other$/i'],
    ];

    /** @throws PackageNotLoadedException */
    public function __construct(private readonly Parser $parser)
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
        $resultRaw = $this->parser->parse($userAgent);
        assert($resultRaw instanceof Client);

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
        $this->hydrateBrowser($result->getBrowser(), $resultRaw->ua);
        // renderingEngine not available
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw->os);
        $this->hydrateDevice($result->getDevice(), $resultRaw->device);

        return $result;
    }

    /** @throws void */
    private function hasResult(Client $resultRaw): bool
    {
        if ($this->isBot($resultRaw) === true) {
            return true;
        }

        if ($this->isRealResult($resultRaw->ua->family)) {
            return true;
        }

        if ($this->isRealResult($resultRaw->os->family)) {
            return true;
        }

        return $this->isRealResult($resultRaw->device->model, 'device', 'model');
    }

    /** @throws void */
    private function isBot(Client $resultRaw): bool
    {
        return $resultRaw->device->family === 'Spider';
    }

    /** @throws void */
    private function hydrateBot(Model\Bot $bot, Client $resultRaw): void
    {
        $bot->setIsBot(true);
        $bot->setName($this->getRealResult($resultRaw->ua->family, 'bot', 'name'));
    }

    /** @throws void */
    private function hydrateBrowser(Model\Browser $browser, UserAgent $uaRaw): void
    {
        $browser->setName($this->getRealResult($uaRaw->family));

        $browser->getVersion()->setMajor($this->getRealResult($uaRaw->major));
        $browser->getVersion()->setMinor($this->getRealResult($uaRaw->minor));
        $browser->getVersion()->setPatch($this->getRealResult($uaRaw->patch));
    }

    /** @throws void */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, OperatingSystem $osRaw): void
    {
        $os->setName($this->getRealResult($osRaw->family));

        $os->getVersion()->setMajor($this->getRealResult($osRaw->major));
        $os->getVersion()->setMinor($this->getRealResult($osRaw->minor));
        $os->getVersion()->setPatch($this->getRealResult($osRaw->patch));
    }

    /** @throws void */
    private function hydrateDevice(Model\Device $device, Device $deviceRaw): void
    {
        $device->setModel($this->getRealResult($deviceRaw->model, 'device', 'model'));
        $device->setBrand($this->getRealResult($deviceRaw->brand, 'device', 'brand'));
    }
}
