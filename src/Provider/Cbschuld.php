<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use Browser;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for donatj/PhpUserAgent
 *
 * @see https://github.com/donatj/PhpUserAgent
 */
final class Cbschuld extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'cbschuld';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/cbschuld/browser.php';

    /**
     * Composer package name
     */
    protected string $packageName = 'cbschuld/browser.php';

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
            'version' => false,
        ],

        'device' => [
            'model' => false,
            'brand' => false,
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
        $browser = new Browser();

        $browser->setUserAgent($userAgent);

        $resultCache = [
            'browserName' => $browser->getBrowser(),
            'browserVersion' => $browser->getVersion(),

            'osName' => $browser->getPlatform(),
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
    }
}
