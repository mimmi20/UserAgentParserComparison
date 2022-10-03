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
    public function __construct(private UserAgent | null $parser = null)
    {
        if (null !== $parser) {
            return;
        }

        $this->checkIfInstalled();
    }

    public function getParser(): UserAgent
    {
        if (null !== $this->parser) {
            return $this->parser;
        }

        $this->parser = new UserAgent();

        return $this->parser;
    }

    /**
     * @param array $headers
     *
     * @throws NoResultFoundException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $parser = $this->getParser();
        $parser->analyze($userAgent);

        $browser  = $parser->browser;
        $os       = $parser->os;
        $device   = $parser->device;
        $platform = $parser->platform;

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
            'platform' => $platform,
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

    /** @param array $browserRaw */
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

    /** @param array $osRaw */
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

    /** @param array $deviceRaw */
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
