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

    protected string $language = 'PHP';

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
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'model' => true,
            'brand' => true,
            'type' => false,
            'isMobile' => false,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => false,
        ],
    ];

    protected array $defaultValues = [
        'general' => ['/^Other$/i'],

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

        'bot' => [
            'name' => [
                '/^Other$/i',
                '/^crawler$/i',
                '/^robot$/i',
                '/^crawl$/i',
                '/^Spider$/i',
            ],
        ],
    ];

    /** @throws PackageNotLoadedException */
    public function __construct(private Parser | null $parser = null)
    {
        if (null !== $parser) {
            return;
        }

        $this->checkIfInstalled();
    }

    public function getParser(): Parser
    {
        if (null !== $this->parser) {
            return $this->parser;
        }

        $this->parser = Parser::create();

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
        assert($resultRaw instanceof Client);

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
        $this->hydrateBrowser($result->getBrowser(), $resultRaw->ua);
        // renderingEngine not available
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw->os);
        $this->hydrateDevice($result->getDevice(), $resultRaw->device);

        return $result;
    }

    private function hasResult(Client $resultRaw): bool
    {
        if (true === $this->isBot($resultRaw)) {
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

    private function isBot(Client $resultRaw): bool
    {
        return 'Spider' === $resultRaw->device->family;
    }

    private function hydrateBot(Model\Bot $bot, Client $resultRaw): void
    {
        $bot->setIsBot(true);
        $bot->setName($this->getRealResult($resultRaw->ua->family, 'bot', 'name'));
    }

    private function hydrateBrowser(Model\Browser $browser, UserAgent $uaRaw): void
    {
        $browser->setName($this->getRealResult($uaRaw->family));

        $browser->getVersion()->setMajor($this->getRealResult($uaRaw->major));
        $browser->getVersion()->setMinor($this->getRealResult($uaRaw->minor));
        $browser->getVersion()->setPatch($this->getRealResult($uaRaw->patch));
    }

    private function hydrateOperatingSystem(Model\OperatingSystem $os, OperatingSystem $osRaw): void
    {
        $os->setName($this->getRealResult($osRaw->family));

        $os->getVersion()->setMajor($this->getRealResult($osRaw->major));
        $os->getVersion()->setMinor($this->getRealResult($osRaw->minor));
        $os->getVersion()->setPatch($this->getRealResult($osRaw->patch));
    }

    private function hydrateDevice(Model\Device $device, Device $deviceRaw): void
    {
        $device->setModel($this->getRealResult($deviceRaw->model, 'device', 'model'));
        $device->setBrand($this->getRealResult($deviceRaw->brand, 'device', 'brand'));
    }
}
