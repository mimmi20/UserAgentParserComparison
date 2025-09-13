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

use BrowscapPHP\Browscap;
use BrowserDetector\Version\Exception\NotNumericException;
use BrowserDetector\Version\NullVersion;
use BrowserDetector\Version\VersionBuilder;
use Override;
use stdClass;
use Throwable;
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

use function array_key_exists;
use function assert;
use function is_string;
use function property_exists;

/**
 * Abstraction for all browscap types
 *
 * @see https://github.com/browscap/browscap-php
 *
 * @phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
        'client' => [
            'name' => [
                '/^General Crawlers/i',
                '/^Generic/i',
                '/^Default Browser$/i',
            ],
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
        if (!array_key_exists('user-agent', $headers) || !is_string($headers['user-agent'])) {
            throw new NoResultFoundException('Can only use the user-agent Header');
        }

        try {
            $resultRaw = $this->parser->getBrowser($headers['user-agent']);
        } catch (Throwable $e) {
            throw new DetectionErroredException(
                'No result found for user agent: ' . $headers['user-agent'],
                0,
                $e,
            );
        }

        assert($resultRaw instanceof stdClass);

        try {
            $resultObject = new Result(
                headers: $headers,
                device: new Device(
                    deviceName: property_exists(
                        $resultRaw,
                        'device_name',
                    ) ? $resultRaw->device_name : null,
                    marketingName: null,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    brand: new Company(
                        type: property_exists(
                            $resultRaw,
                            'device_brand_name',
                        ) ? $resultRaw->device_brand_name : 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    type: property_exists($resultRaw, 'device_type') ? Type::fromName(
                        $resultRaw->device_type,
                    ) : Type::Unknown,
                    display: new Display(
                        width: null,
                        height: null,
                        touch: property_exists(
                            $resultRaw,
                            'device_pointing_method',
                        ) && $resultRaw->device_pointing_method === 'touchscreen' ? true : null,
                        size: null,
                    ),
                    dualOrientation: null,
                    simCount: null,
                ),
                os: new Os(
                    name: property_exists($resultRaw, 'platform') ? $resultRaw->platform : null,
                    marketingName: null,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: property_exists(
                        $resultRaw,
                        'platform_version',
                    ) ? (new VersionBuilder())->set(
                        $resultRaw->platform_version,
                    ) : new NullVersion(),
                    bits: null,
                ),
                browser: new Browser(
                    name: property_exists($resultRaw, 'browser') ? $resultRaw->browser : null,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: property_exists(
                        $resultRaw,
                        'version',
                    ) ? (new VersionBuilder())->set(
                        $resultRaw->version,
                    ) : new NullVersion(),
                    type: property_exists(
                        $resultRaw,
                        'issyndicationreader',
                    ) && $resultRaw->issyndicationreader === true ? \UaBrowserType\Type::BotSyndicationReader : (property_exists(
                        $resultRaw,
                        'crawler',
                    ) && $resultRaw->crawler === true ? \UaBrowserType\Type::Bot : (property_exists(
                        $resultRaw,
                        'browser_type',
                    ) ? \UaBrowserType\Type::fromName(
                        $resultRaw->browser_type,
                    ) : \UaBrowserType\Type::Unknown)),
                    bits: null,
                    modus: null,
                ),
                engine: new Engine(
                    name: property_exists(
                        $resultRaw,
                        'renderingengine_name',
                    ) ? $resultRaw->renderingengine_name : null,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: property_exists(
                        $resultRaw,
                        'renderingengine_version',
                    ) ? (new VersionBuilder())->set(
                        $resultRaw->renderingengine_version,
                    ) : new NullVersion(),
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
            // var_dump($resultRaw, $resultObject);exit;
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

    /** @throws void */
    protected function hasResult(Result $result): bool
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

        return $this->isRealResult($device, 'device', 'model') === true;
    }
}
