<?php

/**
 * This file is part of the mimmi20/user-agent-parser-comparison package.
 *
 * Copyright (c) 2015-2025, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use BrowserDetector\Version\Exception\NotNumericException;
use BrowserDetector\Version\VersionBuilder;
use Override;
use Psr\Cache\CacheItemPoolInterface;
use UaDeviceType\Type;
use UaResult\Browser\Browser;
use UaResult\Company\Company;
use UaResult\Device\Device;
use UaResult\Device\Display;
use UaResult\Engine\Engine;
use UaResult\Os\Os;
use UaResult\Result\Result;
use UserAgentParserComparison\Exception\DetectionErroredException;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;
use WhichBrowser\Parser as WhichBrowserParser;

/**
 * Abstraction for whichbrowser/parser
 *
 * @see https://github.com/WhichBrowser/Parser
 */
final class WhichBrowser extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'WhichBrowser';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/WhichBrowser/Parser';

    /**
     * Composer package name
     */
    protected string $packageName = 'whichbrowser/parser';
    protected string $language    = 'PHP';

    /**
     * Set this in each Provider implementation
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    protected array $detectionCapabilities = [
        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => false,
        ],
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'brand' => true,
            'isMobile' => true,
            'isTouch' => false,
            'model' => true,
            'type' => true,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => true,
            'version' => true,
        ],
    ];

    /** @throws void */
    public function __construct(
        private readonly WhichBrowserParser $parser,
        private readonly CacheItemPoolInterface $cache,
    ) {
        // nothing to do here
    }

    /** @throws void */
    #[Override]
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
     * @throws DetectionErroredException
     */
    #[Override]
    public function parse(array $headers = []): Model\UserAgent
    {
        $this->parser->analyse($headers, ['cache' => $this->cache]);

        /*
         * No result found?
         */
        if ($this->parser->isDetected() !== true) {
            throw new NoResultFoundException(
                'No result found for user agent: ' . ($headers['user-agent'] ?? ''),
            );
        }

        try {
            $resultObject = new Result(
                headers: $headers,
                device: new Device(
                    deviceName: $this->parser->device->getModel(),
                    marketingName: null,
                    manufacturer: new Company(
                        type: $this->parser->device->getManufacturer(),
                        name: null,
                        brandname: null,
                    ),
                    brand: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    type: Type::fromName($this->parser->getType()),
                    display: new Display(
                        width: null,
                        height: null,
                        touch: null,
                        size: null,
                    ),
                    dualOrientation: null,
                    simCount: null,
                ),
                os: new Os(
                    name: $this->parser->os->getName(),
                    marketingName: null,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: (new VersionBuilder())->set(
                        $this->parser->os->getVersion(),
                    ),
                    bits: null,
                ),
                browser: new Browser(
                    name: $this->parser->browser->getName(),
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: (new VersionBuilder())->set(
                        $this->parser->browser->getVersion(),
                    ),
                    type: $this->parser->getType() === 'bot' ? \UaBrowserType\Type::Bot : \UaBrowserType\Type::Unknown,
                    bits: null,
                    modus: null,
                ),
                engine: new Engine(
                    name: $this->parser->engine->getName(),
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: (new VersionBuilder())->set(
                        $this->parser->engine->getVersion(),
                    ),
                ),
            );
        } catch (NotNumericException $e) {
            throw new DetectionErroredException(
                'No result found for user agent: ' . $headers['user-agent'],
                0,
                $e,
            );
        }

        /*
         * No result found?
         */
        if ($this->hasResult($resultObject) !== true) {
            throw new NoResultFoundException(
                'No result found for user agent: ' . ($headers['user-agent'] ?? ''),
            );
        }

        /*
         * Hydrate the model
         */
        return new Model\UserAgent(
            providerName: $this->getName(),
            providerVersion: $this->getVersion(),
            rawResult: $this->parser->toArray(),
            result: $resultObject,
        );
    }

    /** @throws void */
    private function hasResult(Result $result): bool
    {
        if ($result->getBrowser()->getType()->isBot()) {
            return true;
        }

        $client = $result->getBrowser()->getName();

        if ($this->isRealResult($client)) {
            return true;
        }

        $os = $result->getOs()->getName();

        if ($this->isRealResult($os)) {
            return true;
        }

        $engine = $result->getEngine()->getName();

        if ($this->isRealResult($engine)) {
            return true;
        }

        $device = $result->getDevice()->getDeviceName();

        return $this->isRealResult($device);
    }
}
