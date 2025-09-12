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
use BrowserDetector\Version\NullVersion;
use BrowserDetector\Version\VersionBuilder;
use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use Override;
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

use function is_array;

/**
 * Abstraction for matomo/device-detector
 *
 * @see https://github.com/matomo/device-detector
 */
final class MatomoDeviceDetector extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'MatomoDeviceDetector';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/matomo/device-detector';

    /**
     * Composer package name
     */
    protected string $packageName = 'matomo/device-detector';
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
            'type' => true,
        ],
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'brand' => true,
            'isMobile' => true,
            'isTouch' => true,
            'model' => true,
            'type' => true,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => true,
            'version' => false,
        ],
    ];

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'client' => [
            'name' => [
                '/^Bot$/i',
                '/^Generic Bot$/i',
            ],
        ],
        'general' => ['/^UNK$/i'],
    ];

    /** @throws void */
    public function __construct(private readonly DeviceDetector $parser)
    {
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
        $clientHints = ClientHints::factory($headers);

        $this->parser->setUserAgent($headers['user-agent'] ?? '');
        $this->parser->setClientHints($clientHints);
        $this->parser->parse();

        try {
            $resultObject = new Result(
                headers: $headers,
                device: new Device(
                    deviceName: $this->parser->getModel(),
                    marketingName: null,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    brand: new Company(
                        type: $this->parser->getBrandName(),
                        name: null,
                        brandname: null,
                    ),
                    type: Type::fromName($this->parser->getDeviceName()),
                    display: new Display(
                        width: null,
                        height: null,
                        touch: $this->parser->isTouchEnabled() ? true : null,
                        size: null,
                    ),
                    dualOrientation: null,
                    simCount: null,
                ),
                os: new Os(
                    name: is_array($os = $this->parser->getOs('name')) ? null : $os,
                    marketingName: null,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: is_array(
                        $osVersion = $this->parser->getOs('version'),
                    ) ? new NullVersion() : (new VersionBuilder())->set(
                        (string) $osVersion,
                    ),
                    bits: null,
                ),
                browser: new Browser(
                    name: $this->parser->isBot() ? (is_array(
                        $bot = $this->parser->getBot(),
                    ) ? $bot['name'] : null) : $this->parser->getClient(
                        'name',
                    ),
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: ($this->parser->isBot() || is_array(
                        $clientVersion = $this->parser->getClient('version'),
                    )) ? new NullVersion() : (new VersionBuilder())->set(
                        (string) $clientVersion,
                    ),
                    type: $this->parser->isBot() ? \UaBrowserType\Type::fromName(
                        $this->parser->getBot()['category'] ?? '',
                    ) : \UaBrowserType\Type::Unknown,
                    bits: null,
                    modus: null,
                ),
                engine: new Engine(
                    name: is_array($client = $this->parser->getClient('engine')) ? null : $client,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: new NullVersion(),
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
            rawResult: $this->getResultRaw($this->parser),
            result: $resultObject,
        );
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return array{client: array<mixed>|string|null, operatingSystem: array<mixed>|string|null, device: array<string, mixed>, bot: array<mixed>|bool|null, extra: array<string, mixed>}
     *
     * @throws void
     */
    private function getResultRaw(DeviceDetector $dd): array
    {
        return [
            'bot' => $dd->getBot(),
            'client' => $dd->getClient(),

            'device' => [
                'brand' => $dd->getBrandName(),
                'brandName' => $dd->getBrandName(),

                'device' => $dd->getDevice(),
                'deviceName' => $dd->getDeviceName(),

                'model' => $dd->getModel(),
            ],

            'extra' => [
                'isBot' => $dd->isBot(),

                // client
                'isBrowser' => $dd->isBrowser(),

                // deviceType
                'isCamera' => $dd->isCamera(),
                'isCarBrowser' => $dd->isCarBrowser(),
                'isConsole' => $dd->isConsole(),

                // other special
                'isDesktop' => $dd->isDesktop(),
                'isFeaturePhone' => $dd->isFeaturePhone(),
                'isFeedReader' => $dd->isFeedReader(),
                'isLibrary' => $dd->isLibrary(),
                'isMediaPlayer' => $dd->isMediaPlayer(),
                'isMobile' => $dd->isMobile(),
                'isMobileApp' => $dd->isMobileApp(),
                'isPhablet' => $dd->isPhablet(),
                'isPIM' => $dd->isPIM(),
                'isPortableMediaPlayer' => $dd->isPortableMediaPlayer(),
                'isSmartDisplay' => $dd->isSmartDisplay(),
                'isSmartphone' => $dd->isSmartphone(),
                'isTablet' => $dd->isTablet(),
                'isTouchEnabled' => $dd->isTouchEnabled(),
                'isTV' => $dd->isTV(),
            ],
            'operatingSystem' => $dd->getOs(),
        ];
    }

    /** @throws void */
    private function hasResult(Result $result): bool
    {
        if ($result->getBrowser()->getType()->isBot()) {
            return true;
        }

        $client = $result->getBrowser()->getName();

        if ($this->isRealResult($client, 'client', 'name')) {
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
