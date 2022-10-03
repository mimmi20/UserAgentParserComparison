<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use Sinergi\BrowserDetector;
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

    public function getBrowserParser(string $userAgent): BrowserDetector\Browser
    {
        return new BrowserDetector\Browser($userAgent);
    }

    public function getOperatingSystemParser(string $userAgent): BrowserDetector\Os
    {
        return new BrowserDetector\Os($userAgent);
    }

    public function getDeviceParser(string $userAgent): BrowserDetector\Device
    {
        return new BrowserDetector\Device($userAgent);
    }

    /**
     * @throws NoResultFoundException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $browserRaw = $this->getBrowserParser($userAgent);
        $osRaw      = $this->getOperatingSystemParser($userAgent);
        $deviceRaw  = $this->getDeviceParser($userAgent);

        /*
         * No result found?
         */
        if (true !== $this->hasResult($browserRaw, $osRaw, $deviceRaw)) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
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

    private function hasResult(BrowserDetector\Browser $browserRaw, BrowserDetector\Os $osRaw, BrowserDetector\Device $deviceRaw): bool
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

    private function hydrateBrowser(Model\Browser $browser, BrowserDetector\Browser $browserRaw): void
    {
        $browser->setName($this->getRealResult($browserRaw->getName()));
        $browser->getVersion()->setComplete($this->getRealResult($browserRaw->getVersion()));
    }

    private function hydrateOperatingSystem(Model\OperatingSystem $os, BrowserDetector\Os $osRaw): void
    {
        $os->setName($this->getRealResult($osRaw->getName()));
        $os->getVersion()->setComplete($this->getRealResult($osRaw->getVersion()));
    }

    private function hydrateDevice(Model\Device $device, BrowserDetector\Os $osRaw, BrowserDetector\Device $deviceRaw): void
    {
        $device->setModel($this->getRealResult($deviceRaw->getName(), 'device', 'model'));

        if (true !== $osRaw->isMobile()) {
            return;
        }

        $device->setIsMobile(true);
    }
}
