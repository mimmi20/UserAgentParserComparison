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

use Browser;
use Override;
use UaResult\Result\ResultInterface;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

use function array_key_exists;
use function is_string;

/**
 * Abstraction for donatj/PhpUserAgent
 *
 * @see https://github.com/donatj/PhpUserAgent
 */
final class Cbschuld extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'cbschuld';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/cbschuld/browser.php';

    /**
     * Composer package name
     */
    protected string $packageName = 'cbschuld/browser.php';
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
            'isMobile' => false,
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
    public function __construct(private readonly Browser $parser)
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
        if (!array_key_exists('user-agent', $headers) || !is_string($headers['user-agent'])) {
            throw new NoResultFoundException('Can only use the user-agent Header');
        }

        $this->parser->setUserAgent($headers['user-agent']);

        $resultObject = new \UaResult\Result\Result(
            headers: $headers,
            device: new \UaResult\Device\Device(
                deviceName: null,
                marketingName: null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                brand: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                type: $this->parser->isTablet() ? \UaDeviceType\Type::Tablet : ($this->parser->isMobile() ? \UaDeviceType\Type::MobileDevice : \UaDeviceType\Type::Unknown),
                display: new \UaResult\Device\Display(
                    width: null,
                    height: null,
                    touch: null,
                    size: null,
                ),
                dualOrientation: null,
                simCount: null,
            ),
            os: new \UaResult\Os\Os(
                name: $this->parser->getPlatform(),
                marketingName: null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: new \BrowserDetector\Version\NullVersion(),
                bits: null,
            ),
            browser: new \UaResult\Browser\Browser(
                name: $this->parser->getBrowser(),
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: (new \BrowserDetector\Version\VersionBuilder())->set($this->parser->getVersion()),
                type: $this->parser->isRobot() ? \UaBrowserType\Type::Bot : \UaBrowserType\Type::Unknown,
                bits: null,
                modus: null,
            ),
            engine: new \UaResult\Engine\Engine(
                name: null,
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
            rawResult: [
                'browserName' => $this->parser->getBrowser(),
                'browserVersion' => $this->parser->getVersion(),
                'osName' => $this->parser->getPlatform(),
            ],
            result: $resultObject,
        );
    }

    /**
     * @param array{browserName: string, browserVersion: string, osName: string} $resultRaw
     *
     * @throws void
     */
    private function hasResult(ResultInterface $result): bool
    {
        return $this->isRealResult($result->getBrowser()->getName())
            || $this->isRealResult($result->getOs()->getName());
    }
}
