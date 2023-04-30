<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use BrowscapPHP\Browscap;
use BrowscapPHP\Exception;
use stdClass;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Model;

use function assert;
use function property_exists;

/**
 * Abstraction for all browscap types
 *
 * @see https://github.com/browscap/browscap-php
 */
abstract class AbstractBrowscap extends AbstractParseProvider
{
    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/browscap/browscap-php';

    /**
     * Composer package name
     */
    protected string $packageName = 'browscap/browscap-php';
    protected string $language    = 'PHP';

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'bot' => [
            'name' => [
                '/^General Crawlers/i',
                '/^Generic/i',
            ],
        ],

        'browser' => [
            'name' => ['/^Default Browser$/i'],
        ],

        'device' => [
            'model' => [
                '/^general/i',
                '/desktop$/i',
            ],
        ],
        'general' => ['/^unknown$/i'],
    ];

    /** @throws void */
    public function __construct(private readonly Browscap $parser)
    {
    }

    /**
     * @throws NoResultFoundException
     * @throws Exception
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $resultRaw = $this->parser->getBrowser($userAgent);
        assert($resultRaw instanceof stdClass);

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
         * Bot detection (does only work with full_php_browscap.ini)
         */
        if ($this->isBot($resultRaw) === true) {
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

    /** @throws void */
    protected function hasResult(stdClass $resultRaw): bool
    {
        if (property_exists($resultRaw, 'crawler') && $resultRaw->crawler === true) {
            return true;
        }

        if (
            property_exists($resultRaw, 'browser')
            && $this->isRealResult($resultRaw->browser, 'browser', 'name') === true
        ) {
            return true;
        }

        if (
            property_exists($resultRaw, 'platform')
            && $this->isRealResult($resultRaw->platform) === true
        ) {
            return true;
        }

        if (
            property_exists($resultRaw, 'renderingengine_name')
            && $this->isRealResult($resultRaw->renderingengine_name) === true
        ) {
            return true;
        }

        return property_exists($resultRaw, 'device_name') && $this->isRealResult(
            $resultRaw->device_name,
        ) === true;
    }

    /** @throws void */
    protected function isBot(stdClass $resultRaw): bool
    {
        return property_exists($resultRaw, 'crawler') && $resultRaw->crawler === true;
    }

    /** @throws void */
    protected function hydrateBot(Model\Bot $bot, stdClass $resultRaw): void
    {
        $bot->setIsBot(true);

        if (property_exists($resultRaw, 'browser')) {
            $bot->setName($this->getRealResult($resultRaw->browser, 'bot', 'name'));
        }

        if (
            property_exists($resultRaw, 'issyndicationreader')
            && $resultRaw->issyndicationreader === true
        ) {
            $bot->setType('RSS');
        } elseif (property_exists($resultRaw, 'browser_type')) {
            $bot->setType($this->getRealResult($resultRaw->browser_type));
        }
    }

    /** @throws void */
    protected function hydrateBrowser(Model\Browser $browser, stdClass $resultRaw): void
    {
        if (property_exists($resultRaw, 'browser')) {
            $browser->setName($this->getRealResult($resultRaw->browser, 'browser', 'name'));
        }

        if (!property_exists($resultRaw, 'version')) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->version));
    }

    /** @throws void */
    protected function hydrateRenderingEngine(Model\RenderingEngine $engine, stdClass $resultRaw): void
    {
        if (property_exists($resultRaw, 'renderingengine_name')) {
            $engine->setName($this->getRealResult($resultRaw->renderingengine_name));
        }

        if (!property_exists($resultRaw, 'renderingengine_version')) {
            return;
        }

        $engine->getVersion()->setComplete($this->getRealResult($resultRaw->renderingengine_version));
    }

    /** @throws void */
    protected function hydrateOperatingSystem(Model\OperatingSystem $os, stdClass $resultRaw): void
    {
        if (property_exists($resultRaw, 'platform')) {
            $os->setName($this->getRealResult($resultRaw->platform));
        }

        if (!property_exists($resultRaw, 'platform_version')) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($resultRaw->platform_version));
    }

    /** @throws void */
    protected function hydrateDevice(Model\Device $device, stdClass $resultRaw): void
    {
        if (property_exists($resultRaw, 'device_name')) {
            $device->setModel($this->getRealResult($resultRaw->device_name, 'device', 'model'));
        }

        if (property_exists($resultRaw, 'device_brand_name')) {
            $device->setBrand($this->getRealResult($resultRaw->device_brand_name));
        }

        if (property_exists($resultRaw, 'device_type')) {
            $device->setType($this->getRealResult($resultRaw->device_type));
        }

        if (property_exists($resultRaw, 'ismobiledevice') && $resultRaw->ismobiledevice === true) {
            $device->setIsMobile(true);
        }

        if (
            !property_exists($resultRaw, 'device_pointing_method')
            || $resultRaw->device_pointing_method !== 'touchscreen'
        ) {
            return;
        }

        $device->setIsTouch(true);
    }
}
