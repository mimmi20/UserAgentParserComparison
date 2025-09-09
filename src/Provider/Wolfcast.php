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
    protected string $language    = 'PHP';

    /**
     * Set this in each Provider implementation
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    protected array $detectionCapabilities = [
        'bot' => [
            'isBot' => false,
            'name' => false,
            'type' => false,
        ],
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'brand' => false,
            'isMobile' => true,
            'isTouch' => false,
            'model' => false,
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

    /** @throws void */
    public function __construct(private readonly BrowserDetection $parser)
    {
        // nothing to do here
    }

    /**
     * @throws void
     */
    public function isActive(): bool
    {
        try {
            $this->checkIfInstalled();
        } catch (PackageNotLoadedException) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, string> $headers
     *
     * @throws NoResultFoundException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(array $headers = []): Model\UserAgent
    {
        if (!array_key_exists('user-agent', $headers) || !is_string($headers['user-agent'])) {
            throw new NoResultFoundException('Can only use the user-agent Header');
        }

        $this->parser->setUserAgent($headers['user-agent']);

        $resultCache = [
            'browserName' => $this->parser->getName(),
            'browserVersion' => $this->parser->getVersion(),

            'isMobile' => $this->parser->isMobile(),

            'osName' => $this->parser->getPlatform(),
            'osVersion' => $this->parser->getPlatformVersion(true),
        ];

        if ($this->hasResult($resultCache) !== true) {
            throw new NoResultFoundException('No result found for user agent: ' . $headers['user-agent']);
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

    /** @throws void */
    private function hasResult(array $resultRaw): bool
    {
        return $this->isRealResult($resultRaw['browserName']) || $this->isRealResult(
            $resultRaw['osName'],
        );
    }

    /** @throws void */
    private function hydrateBrowser(Model\Browser $browser, array $resultRaw): void
    {
        $browser->setName($this->getRealResult($resultRaw['browserName']));
        $browser->getVersion()->setComplete($this->getRealResult($resultRaw['browserVersion']));
    }

    /** @throws void */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, array $resultRaw): void
    {
        if ($this->isRealResult($resultRaw['osName']) !== true) {
            return;
        }

        $os->setName($resultRaw['osName']);
        $os->getVersion()->setComplete($this->getRealResult($resultRaw['osVersion']));
    }

    /** @throws void */
    private function hydrateDevice(Model\Device $device, array $resultRaw): void
    {
        if ($resultRaw['isMobile'] !== true) {
            return;
        }

        $device->setIsMobile(true);
    }
}
