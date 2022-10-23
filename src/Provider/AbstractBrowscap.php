<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use BrowscapPHP\Browscap;
use BrowscapPHP\Exception;
use stdClass;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Model;

use function assert;

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

    protected string $language = 'PHP';

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'general' => ['/^unknown$/i'],

        'browser' => [
            'name' => ['/^Default Browser$/i'],
        ],

        'device' => [
            'model' => [
                '/^general/i',
                '/desktop$/i',
            ],
        ],

        'bot' => [
            'name' => [
                '/^General Crawlers/i',
                '/^Generic/i',
            ],
        ],
    ];

    public function __construct(private Browscap $parser)
    {
    }

    public function getParser(): Browscap
    {
        return $this->parser;
    }

    /**
     * @throws NoResultFoundException
     * @throws Exception
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $parser = $this->getParser();

        $resultRaw = $parser->getBrowser($userAgent);
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

    protected function hasResult(stdClass $resultRaw): bool
    {
        return isset($resultRaw->browser) && true === $this->isRealResult($resultRaw->browser, 'browser', 'name');
    }

    protected function isBot(stdClass $resultRaw): bool
    {
        return isset($resultRaw->crawler) && true === $resultRaw->crawler;
    }

    protected function hydrateBot(Model\Bot $bot, stdClass $resultRaw): void
    {
        $bot->setIsBot(true);

        if (isset($resultRaw->browser)) {
            $bot->setName($this->getRealResult($resultRaw->browser, 'bot', 'name'));
        }

        if (isset($resultRaw->issyndicationreader) && true === $resultRaw->issyndicationreader) {
            $bot->setType('RSS');
        } elseif (isset($resultRaw->browser_type)) {
            $bot->setType($this->getRealResult($resultRaw->browser_type));
        }
    }

    protected function hydrateBrowser(Model\Browser $browser, stdClass $resultRaw): void
    {
        if (isset($resultRaw->browser)) {
            $browser->setName($this->getRealResult($resultRaw->browser, 'browser', 'name'));
        }

        if (!isset($resultRaw->version)) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->version));
    }

    protected function hydrateRenderingEngine(Model\RenderingEngine $engine, stdClass $resultRaw): void
    {
        if (isset($resultRaw->renderingengine_name)) {
            $engine->setName($this->getRealResult($resultRaw->renderingengine_name));
        }

        if (!isset($resultRaw->renderingengine_version)) {
            return;
        }

        $engine->getVersion()->setComplete($this->getRealResult($resultRaw->renderingengine_version));
    }

    protected function hydrateOperatingSystem(Model\OperatingSystem $os, stdClass $resultRaw): void
    {
        if (isset($resultRaw->platform)) {
            $os->setName($this->getRealResult($resultRaw->platform));
        }

        if (!isset($resultRaw->platform_version)) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($resultRaw->platform_version));
    }

    protected function hydrateDevice(Model\Device $device, stdClass $resultRaw): void
    {
        if (isset($resultRaw->device_name)) {
            $device->setModel($this->getRealResult($resultRaw->device_name, 'device', 'model'));
        }

        if (isset($resultRaw->device_brand_name)) {
            $device->setBrand($this->getRealResult($resultRaw->device_brand_name));
        }

        if (isset($resultRaw->device_type)) {
            $device->setType($this->getRealResult($resultRaw->device_type));
        }

        if (isset($resultRaw->ismobiledevice) && true === $this->isRealResult($resultRaw->ismobiledevice) && true === $resultRaw->ismobiledevice) {
            $device->setIsMobile(true);
        }

        if (!isset($resultRaw->device_pointing_method) || 'touchscreen' !== $resultRaw->device_pointing_method) {
            return;
        }

        $device->setIsTouch(true);
    }
}
