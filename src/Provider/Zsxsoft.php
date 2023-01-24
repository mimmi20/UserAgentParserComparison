<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use UserAgent;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for zsxsoft/php-useragent
 *
 * @see https://github.com/zsxsoft/php-useragent
 */
final class Zsxsoft extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'Zsxsoft';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/zsxsoft/php-useragent';

    /**
     * Composer package name
     */
    protected string $packageName = 'zsxsoft/php-useragent';

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
            'isBot' => false,
            'name' => false,
            'type' => false,
        ],
    ];

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'general' => ['/^Unknown$/i'],

        'browser' => [
            'name' => ['/^Mozilla Compatible$/i'],
        ],

        'device' => [
            'model' => [
                '/^Browser$/i',
                '/^Android$/i',
            ],
        ],
    ];

    /** @throws PackageNotLoadedException */
    public function __construct(private readonly UserAgent $parser)
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
        $this->parser->analyze($userAgent);

        $browser  = $this->parser->browser;
        $os       = $this->parser->os;
        $device   = $this->parser->device;

        /*
         * No result found?
         */
        if (true !== $this->hasResult($browser, $os, $device)) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw([
            'browser' => $browser,
            'os' => $os,
            'device' => $device,
            'platform' => $this->parser->platform,
        ]);

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $browser);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $os);
        $this->hydrateDevice($result->getDevice(), $device);

        return $result;
    }

    private function hasResult(array $browser, array $os, array $device): bool
    {
        if (isset($browser['name']) && $this->isRealResult($browser['name'], 'browser', 'name')) {
            return true;
        }

        if (isset($os['name']) && $this->isRealResult($os['name'])) {
            return true;
        }

        if (isset($device['brand']) && $this->isRealResult($device['brand'])) {
            return true;
        }

        return isset($device['model']) && $this->isRealResult($device['model'], 'device', 'model');
    }

    private function hydrateBrowser(Model\Browser $browser, array $browserRaw): void
    {
        if (isset($browserRaw['name'])) {
            $browser->setName($this->getRealResult($browserRaw['name'], 'browser', 'name'));
        }

        if (!isset($browserRaw['version'])) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($browserRaw['version']));
    }

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

    private function hydrateDevice(Model\Device $device, array $deviceRaw): void
    {
        if (isset($deviceRaw['model'])) {
            $device->setModel($this->getRealResult($deviceRaw['model'], 'device', 'model'));
        }

        if (!isset($deviceRaw['brand'])) {
            return;
        }

        $device->setBrand($this->getRealResult($deviceRaw['brand']));
    }
}
