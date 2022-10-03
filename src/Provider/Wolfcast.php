<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;
use Wolfcast\BrowserDetection;

/**
 * Abstraction for donatj/PhpUserAgent
 *
 * @see https://github.com/donatj/PhpUserAgent
 */
final class Wolfcast extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'wolfcast';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/wolfcast/browser-detection';

    /**
     * Composer package name
     */
    protected string $packageName = 'wolfcast/browser-detection';

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
            'model' => false,
            'brand' => false,
            'type' => false,
            'isMobile' => true,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => false,
            'name' => false,
            'type' => false,
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
    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $result = new BrowserDetection($userAgent);

        $resultCache = [
            'browserName' => $result->getName(),
            'browserVersion' => $result->getVersion(),

            'osName' => $result->getPlatform(),
            'osVersion' => $result->getPlatformVersion(true),

            'isMobile' => $result->isMobile(),
        ];

        if (true !== $this->hasResult($resultCache)) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($resultCache);

        /*
         * Bot detection - is currently not possible!
         */

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultCache);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultCache);
        $this->hydrateDevice($result->getDevice(), $resultCache);

        return $result;
    }

    /** @param array $resultRaw */
    private function hasResult(array $resultRaw): bool
    {
        return $this->isRealResult($resultRaw['browserName']) || $this->isRealResult($resultRaw['osName']);
    }

    /** @param array $resultRaw */
    private function hydrateBrowser(Model\Browser $browser, array $resultRaw): void
    {
        $browser->setName($this->getRealResult($resultRaw['browserName']));
        $browser->getVersion()->setComplete($this->getRealResult($resultRaw['browserVersion']));
    }

    /** @param array $resultRaw */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, array $resultRaw): void
    {
        if (true !== $this->isRealResult($resultRaw['osName'])) {
            return;
        }

        $os->setName($resultRaw['osName']);
        $os->getVersion()->setComplete($this->getRealResult($resultRaw['osVersion']));
    }

    /** @param array $resultRaw */
    private function hydrateDevice(Model\Device $device, array $resultRaw): void
    {
        if (true !== $resultRaw['isMobile']) {
            return;
        }

        $device->setIsMobile(true);
    }
}
