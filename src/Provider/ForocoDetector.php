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
use UaResult\Result\ResultInterface;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;
use foroco\BrowserDetection;

use function array_key_exists;
use function is_string;

final class ForocoDetector extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'foroco-browser-detection';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/foroco/php-browser-detection';

    /**
     * Composer package name
     */
    protected string $packageName = 'foroco/php-browser-detection';
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
            'type' => true,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
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
     */
    #[Override]
    public function parse(array $headers = []): Model\UserAgent
    {
        if (!array_key_exists('user-agent', $headers) || !is_string($headers['user-agent'])) {
            throw new NoResultFoundException('Can only use the user-agent Header');
        }

        $bc = new BrowserDetection();

        $r         = $bc->getAll($headers['user-agent']);

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
                type: \UaDeviceType\Type::fromName($r['device_type']),
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
                name: $r['os_name'],
                marketingName: null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: (new \BrowserDetector\Version\VersionBuilder())->set($r['os_version']),
                bits: null,
            ),
            browser: new \UaResult\Browser\Browser(
                name: $r['browser_name'],
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: (new \BrowserDetector\Version\VersionBuilder())->set($r['browser_version']),
                type: \UaBrowserType\Type::Unknown,
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
            rawResult: $r,
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
