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

use Override;
use stdClass;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Model;

use function array_key_exists;
use function assert;
use function get_browser;
use function is_string;

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
            'version' => true,
        ],
    ];

    /** @throws void */
    public function __construct()
    {
        // nothing to do here
    }

    /** @throws void */
    #[Override]
    public function isActive(): bool
    {
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
        if (!array_key_exists('user-agent', $headers) || !is_string($headers['user-agent'])) {
            throw new NoResultFoundException('Can only use the user-agent Header');
        }

        $resultRaw = get_browser($headers['user-agent'], false);
        assert($resultRaw instanceof stdClass);

        $resultObject = new \UaResult\Result\Result(
            headers: $headers,
            device: new \UaResult\Device\Device(
                deviceName: property_exists($resultRaw, 'device_name') ? $resultRaw->device_name : null,
                marketingName: null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                brand: new \UaResult\Company\Company(
                    type: property_exists($resultRaw, 'device_brand_name') ? $resultRaw->device_brand_name : 'unknown',
                    name: null,
                    brandname: null,
                ),
                type: property_exists($resultRaw, 'device_type') ? \UaDeviceType\Type::fromName($resultRaw->device_type) : \UaDeviceType\Type::Unknown,
                display: new \UaResult\Device\Display(
                    width: null,
                    height: null,
                    touch: property_exists($resultRaw, 'device_pointing_method') && $resultRaw->device_pointing_method === 'touchscreen' ? true : null,
                    size: null,
                ),
                dualOrientation: null,
                simCount: null,
            ),
            os: new \UaResult\Os\Os(
                name: property_exists($resultRaw, 'platform') ? $resultRaw->platform : null,
                marketingName: null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: property_exists($resultRaw, 'platform_version') ? (new \BrowserDetector\Version\VersionBuilder())->set($resultRaw['platform_version']) : new \BrowserDetector\Version\NullVersion(),
                bits: null,
            ),
            browser: new \UaResult\Browser\Browser(
                name: property_exists($resultRaw, 'browser') ? $resultRaw['browser'] : null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: property_exists($resultRaw, 'version') ? (new \BrowserDetector\Version\VersionBuilder())->set($resultRaw['version']) : new \BrowserDetector\Version\NullVersion(),
                type: property_exists($resultRaw, 'issyndicationreader') && $resultRaw->issyndicationreader === true ? \UaBrowserType\Type::BotSyndicationReader : ( property_exists($resultRaw, 'browser_type') ? \UaBrowserType\Type::fromName($resultRaw->browser_type) : \UaBrowserType\Type::Unknown),
                bits: null,
                modus: null,
            ),
            engine: new \UaResult\Engine\Engine(
                name: property_exists($resultRaw, 'renderingengine_name') ? $resultRaw->renderingengine_name : null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: property_exists($resultRaw, 'renderingengine_version') ? (new \BrowserDetector\Version\VersionBuilder())->set($resultRaw['renderingengine_version']) : new \BrowserDetector\Version\NullVersion(),
            ),
        );

        /*
         * No result found?
         */
        if ($this->hasResult($resultObject) !== true) {
            throw new NoResultFoundException(
                'No result found for user agent: ' . $headers['user-agent'],
            );
        }

        /*
         * Hydrate the model
         */
        return new Model\UserAgent(
            providerName: $this->getName(),
            providerVersion: $this->getVersion(),
            rawResult: (array) $resultRaw,
            result: $resultObject,
        );
    }
}
