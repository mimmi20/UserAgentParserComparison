<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use Sinergi\BrowserDetector\Browser;
use Sinergi\BrowserDetector\BrowserDetector;
use Sinergi\BrowserDetector\Device;
use Sinergi\BrowserDetector\DeviceDetector;
use Sinergi\BrowserDetector\InvalidArgumentException;
use Sinergi\BrowserDetector\Os;
use Sinergi\BrowserDetector\OsDetector;
use Sinergi\BrowserDetector\UserAgent;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for sinergi/browser-detector
 *
 * @see https://github.com/sinergi/php-browser-detector
 */
final class SinergiBrowserDetector extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'SinergiBrowserDetector';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/sinergi/php-browser-detector';

    /**
     * Composer package name
     */
    protected string $packageName = 'sinergi/browser-detector';

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
            'brand' => false,
            'type' => false,
            'isMobile' => true,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => false,
            'type' => false,
        ],
    ];

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'general' => ['/^unknown$/i'],

        'device' => [
            'model' => ['/^Windows Phone$/i'],
        ],
    ];

    /** @throws PackageNotLoadedException */
    public function __construct()
    {
        $this->checkIfInstalled();
    }

    /**
     * @throws NoResultFoundException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(string $useragent, array $headers = []): Model\UserAgent
    {
        $userAgent = new UserAgent($useragent);

        try {
            $browserRaw = new Browser($userAgent);
            BrowserDetector::detect($browserRaw, $userAgent);

            $osRaw = new Os($userAgent);
            OsDetector::detect($osRaw, $userAgent);

            $deviceRaw = new Device($userAgent);
            DeviceDetector::detect($deviceRaw, $userAgent);
        } catch (InvalidArgumentException $e) {
            throw new NoResultFoundException('No result found for user agent: ' . $useragent, 0, $e);
        }

        /*
         * No result found?
         */
        if (true !== $this->hasResult($browserRaw, $osRaw, $deviceRaw)) {
            throw new NoResultFoundException('No result found for user agent: ' . $useragent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw([
            'browser' => $browserRaw,
            'operatingSystem' => $osRaw,
            'device' => $deviceRaw,
        ]);

        /*
         * Bot detection
         */
        if (true === $browserRaw->isRobot()) {
            $bot = $result->getBot();
            $bot->setIsBot(true);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $browserRaw);
        // renderingEngine not available
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $osRaw);
        $this->hydrateDevice($result->getDevice(), $osRaw, $deviceRaw);

        return $result;
    }

    private function hasResult(Browser $browserRaw, Os $osRaw, Device $deviceRaw): bool
    {
        if ($this->isRealResult($browserRaw->getName())) {
            return true;
        }

        if ($this->isRealResult($osRaw->getName())) {
            return true;
        }

        if ($this->isRealResult($deviceRaw->getName(), 'device', 'model')) {
            return true;
        }

        return true === $browserRaw->isRobot();
    }

    private function hydrateBrowser(Model\Browser $browser, Browser $browserRaw): void
    {
        $browser->setName($this->getRealResult($browserRaw->getName()));
        $browser->getVersion()->setComplete($this->getRealResult($browserRaw->getVersion()));
    }

    private function hydrateOperatingSystem(Model\OperatingSystem $os, Os $osRaw): void
    {
        $os->setName($this->getRealResult($osRaw->getName()));
        $os->getVersion()->setComplete($this->getRealResult($osRaw->getVersion()));
    }

    private function hydrateDevice(Model\Device $device, Os $osRaw, Device $deviceRaw): void
    {
        $device->setModel($this->getRealResult($deviceRaw->getName(), 'device', 'model'));

        if (true !== $osRaw->isMobile()) {
            return;
        }

        $device->setIsMobile(true);
    }
}
