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

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use Override;
use UaResult\Result\ResultInterface;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

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
     */
    #[Override]
    public function parse(array $headers = []): Model\UserAgent
    {
        $clientHints = ClientHints::factory($headers);

        $this->parser->setUserAgent($headers['user-agent'] ?? '');
        $this->parser->setClientHints($clientHints);
        $this->parser->parse();

        $resultObject = new \UaResult\Result\Result(
            headers: $headers,
            device: new \UaResult\Device\Device(
                deviceName: $this->parser->getModel(),
                marketingName: null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                brand: new \UaResult\Company\Company(
                    type: $this->parser->getBrandName(),
                    name: null,
                    brandname: null,
                ),
                type: \UaDeviceType\Type::fromName($this->parser->getDeviceName()),
                display: new \UaResult\Device\Display(
                    width: null,
                    height: null,
                    touch: $this->parser->isTouchEnabled() ? true : null,
                    size: null,
                ),
                dualOrientation: null,
                simCount: null,
            ),
            os: new \UaResult\Os\Os(
                name: $this->parser->getOs('name'),
                marketingName: null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: (new \BrowserDetector\Version\VersionBuilder())->set($this->parser->getOs('version')),
                bits: null,
            ),
            browser: new \UaResult\Browser\Browser(
                name: $this->parser->isBot() ? $this->parser->getBot()['name'] : $this->parser->getClient('name'),
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: $this->parser->isBot() ? new \BrowserDetector\Version\NullVersion() : (new \BrowserDetector\Version\VersionBuilder())->set($this->parser->getClient('version')),
                type: $this->parser->isBot() ? \UaBrowserType\Type::fromName($this->parser->getBot()['category'] ?? '') : \UaBrowserType\Type::Unknown,
                bits: null,
                modus: null,
            ),
            engine: new \UaResult\Engine\Engine(
                name: $this->parser->getClient('engine'),
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: new \BrowserDetector\Version\NullVersion(),
            ),
        );

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
    private function hasResult(ResultInterface $result): bool
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
