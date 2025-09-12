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

use EndorphinStudio\Detector as EndorphinDetector;
use EndorphinStudio\Detector\Data\Result;
use Override;
use Throwable;
use UaResult\Result\ResultInterface;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

use function array_key_exists;
use function is_string;

/**
 * Abstraction for endorphin-studio/browser-detector
 *
 * @see https://github.com/EndorphinDetector-studio/browser-detector
 */
final class Endorphin extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'Endorphin';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/endorphin-studio/browser-detector';

    /**
     * Composer package name
     */
    protected string $packageName = 'endorphin-studio/browser-detector';
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

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'device' => [
            'model' => ['/^Desktop/i'],
        ],
        'general' => ['/^N\\\A$/i'],
    ];

    /** @throws void */
    public function __construct(private readonly EndorphinDetector\Detector $parser)
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
     * @throws \UserAgentParserComparison\Exception\DetectionErroredException
     */
    #[Override]
    public function parse(array $headers = []): Model\UserAgent
    {
        if (!array_key_exists('user-agent', $headers) || !is_string($headers['user-agent'])) {
            throw new NoResultFoundException('Can only use the user-agent Header');
        }

        try {
            $resultRaw = $this->parser->analyse($headers['user-agent']);
        } catch (Throwable $e) {
            throw new \UserAgentParserComparison\Exception\DetectionErroredException(
                'No result found for user agent: ' . $headers['user-agent'],
                0,
                $e,
            );
        }

        $resultObject = new \UaResult\Result\Result(
            headers: $headers,
            device: new \UaResult\Device\Device(
                deviceName: $resultRaw->getDevice()->getName(),
                marketingName: $resultRaw->getDevice()->getModel()?->getModel(),
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
                type: \UaDeviceType\Type::fromName($resultRaw->getDevice()->getType()),
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
                name: $resultRaw->getOs()->getName(),
                marketingName: null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: (new \BrowserDetector\Version\VersionBuilder())->set($resultRaw->getOs()->getVersion()),
                bits: null,
            ),
            browser: new \UaResult\Browser\Browser(
                name: $resultRaw->getRobot()->getType() !== null ? $resultRaw->getRobot()->getName() : $resultRaw->getBrowser()->getName(),
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: (new \BrowserDetector\Version\VersionBuilder())->set($resultRaw->getBrowser()->getVersion()),
                type: $resultRaw->getRobot()->getType() !== null ? \UaBrowserType\Type::fromName($resultRaw->getRobot()->getType()) : \UaBrowserType\Type::Unknown,
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
                'No result found for user agent: ' . $headers['user-agent'],
            );
        }

        /*
         * Hydrate the model
         */
        return new Model\UserAgent(
            providerName: $this->getName(),
            providerVersion: $this->getVersion(),
            rawResult: \json_decode(\json_encode($resultRaw), true),
            result: $resultObject,
        );
    }

    /** @throws void */
    private function hasResult(ResultInterface $result): bool
    {
        if ($this->isRealResult($result->getOs()->getName()) === true) {
            return true;
        }

        if ($this->isRealResult($result->getBrowser()->getName()) === true) {
            return true;
        }

        if ($this->isRealResult($result->getDevice()->getDeviceName(), 'device', 'model') === true) {
            return true;
        }

        return $this->isRealResult($result->getBrowser()->getType()->getType()) === true;
    }
}
