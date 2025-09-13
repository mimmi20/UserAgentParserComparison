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
use Fyre\Http\UserAgent;
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

use function array_key_exists;
use function is_string;

final class FyreUseragent extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'fyre-useragent';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/fyre/useragent';

    /**
     * Composer package name
     */
    protected string $packageName = 'fyre/useragent';
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
            'version' => false,
        ],

        'renderingEngine' => [
            'name' => false,
            'version' => false,
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

        $userAgent = new UserAgent($headers['user-agent']);
        $browser   = $userAgent->getBrowser();
        $version   = $userAgent->getVersion();
        $os        = $userAgent->getPlatform();
        $isMobile  = $userAgent->isMobile();
        $isBot     = $userAgent->isRobot();

        try {
            $resultObject = new Result(
                headers: $headers,
                device: new Device(
                    deviceName: null,
                    marketingName: null,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    brand: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    type: $isMobile ? Type::MobileDevice : Type::Unknown,
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
                    name: $os,
                    marketingName: null,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: new NullVersion(),
                    bits: null,
                ),
                browser: new Browser(
                    name: $browser,
                    manufacturer: new Company(
                        type: 'unknown',
                        name: null,
                        brandname: null,
                    ),
                    version: (new VersionBuilder())->set((string) $version),
                    type: $isBot ? \UaBrowserType\Type::Bot : \UaBrowserType\Type::Unknown,
                    bits: null,
                    modus: null,
                ),
                engine: new Engine(
                    name: null,
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
            rawResult: [
                'browser' => $browser,
                'version' => $version,
                'os' => $os,
                'isMobile' => $isMobile,
                'isBot' => $isBot,
            ],
            result: $resultObject,
        );
    }

    /** @throws void */
    private function hasResult(Result $result): bool
    {
        return $this->isRealResult($result->getBrowser()->getName())
            || $this->isRealResult($result->getOs()->getName());
    }
}
