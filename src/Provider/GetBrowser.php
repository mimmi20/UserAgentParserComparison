<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use stdClass;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Model;

use function assert;
use function get_browser;

/**
 * Abstraction for Browscap full type
 *
 * @see https://github.com/browscap/browscap-php
 */
final class GetBrowser extends AbstractBrowscap
{
    /**
     * Name of the provider
     */
    protected string $name = 'PHP Native get_browser';

    /**
     * Homepage of the provider
     */
    protected string $homepage = '';

    /**
     * Composer package name
     */
    protected string $packageName = '';

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
            'name' => true,
            'version' => true,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'model' => true,
            'brand' => true,
            'type' => true,
            'isMobile' => true,
            'isTouch' => true,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => true,
        ],
    ];

    /** @throws void */
    public function __construct()
    {
        // nothing to do here
    }

    /**
     * @throws NoResultFoundException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(
        string $userAgent,
        array $headers = [],
    ): Model\UserAgent {
        $resultRaw = get_browser($userAgent, false);
        assert($resultRaw instanceof stdClass);

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
         * Bot detection (does only work with full_php_browscap.ini)
         */
        if (true === $this->isBot($resultRaw)) {
            $this->hydrateBot($result->getBot(), $resultRaw);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw);
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $resultRaw);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw);
        $this->hydrateDevice($result->getDevice(), $resultRaw);

        return $result;
    }
}
