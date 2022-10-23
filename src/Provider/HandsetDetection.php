<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use HandsetDetection as Parser;
use UserAgentParserComparison\Exception\InvalidArgumentException;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for ua-parser/uap-php
 *
 * @see https://github.com/HandsetDetection/php-apikit
 */
final class HandsetDetection extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'HandsetDetection';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/HandsetDetection/php-apikit';

    /**
     * Composer package name
     */
    protected string $packageName = 'handsetdetection/php-apikit';

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
        'general' => ['/^generic$/i'],

        'device' => [
            'model' => [
                '/analyzer/i',
                '/bot/i',
                '/crawler/i',
                '/library/i',
                '/spider/i',
            ],
        ],
    ];

    public function __construct(private Parser\HD4 $parser)
    {
    }

    public function getParser(): Parser\HD4
    {
        return $this->parser;
    }

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $headers['User-Agent'] = $userAgent;

        $parser = $this->getParser();

        /*
         * No result found?
         */
        $result    = $parser->deviceDetect($headers);
        $resultRaw = $parser->getReply();

        if (true !== $result) {
            if (isset($resultRaw['status']) && '299' === $resultRaw['status']) {
                throw new InvalidArgumentException('You need to warm-up the cache first to use this provider');
            }

            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * No result found?
         */
        if (!isset($resultRaw['hd_specs']) || true !== $this->hasResult($resultRaw['hd_specs'])) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($resultRaw['hd_specs']);

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw['hd_specs']);
        // renderingEngine not available
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw['hd_specs']);
        $this->hydrateDevice($result->getDevice(), $resultRaw['hd_specs']);

        return $result;
    }

    private function hasResult(array $resultRaw): bool
    {
        if (isset($resultRaw['general_browser']) && $this->isRealResult($resultRaw['general_browser'])) {
            return true;
        }

        if (isset($resultRaw['general_platform']) && $this->isRealResult($resultRaw['general_platform'])) {
            return true;
        }

        return isset($resultRaw['general_model']) && $this->isRealResult($resultRaw['general_model'], 'device', 'model') && $this->isRealResult($resultRaw['general_vendor'], 'device', 'brand');
    }

    private function hydrateBrowser(Model\Browser $browser, array $resultRaw): void
    {
        if (isset($resultRaw['general_browser'])) {
            $browser->setName($this->getRealResult($resultRaw['general_browser']));
        }

        if (!isset($resultRaw['general_browser_version'])) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($resultRaw['general_browser_version']));
    }

    private function hydrateOperatingSystem(Model\OperatingSystem $os, array $resultRaw): void
    {
        if (isset($resultRaw['general_platform'])) {
            $os->setName($this->getRealResult($resultRaw['general_platform']));
        }

        if (!isset($resultRaw['general_platform_version'])) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($resultRaw['general_platform_version']));
    }

    /** @param Model\UserAgent $device */
    private function hydrateDevice(Model\Device $device, array $resultRaw): void
    {
        if (!isset($resultRaw['general_model']) || !$this->isRealResult($resultRaw['general_model'], 'device', 'model') || !isset($resultRaw['general_vendor']) || !$this->isRealResult($resultRaw['general_vendor'], 'device', 'brand')) {
            return;
        }

        $device->setModel($this->getRealResult($resultRaw['general_model'], 'device', 'model'));
        $device->setBrand($this->getRealResult($resultRaw['general_vendor'], 'device', 'brand'));
    }
}
