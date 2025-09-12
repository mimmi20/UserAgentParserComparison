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

use Detection\Exception\MobileDetectException;
use Override;
use UaResult\Result\ResultInterface;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

use function array_key_exists;
use function is_string;

/** @see https://github.com/browscap/browscap-php */
final class MobileDetect extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'MobileDetect';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/serbanghita/Mobile-Detect';

    /**
     * Composer package name
     */
    protected string $packageName = 'mobiledetect/mobiledetectlib';
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
            'name' => false,
            'version' => false,
        ],

        'device' => [
            'brand' => false,
            'isMobile' => true,
            'isTouch' => false,
            'model' => false,
            'type' => false,
        ],

        'operatingSystem' => [
            'name' => false,
            'version' => false,
        ],

        'renderingEngine' => [
            'name' => false,
            'version' => false,
        ],
    ];

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

        $parser = new \Detection\MobileDetect();
        $parser->setHttpHeaders($headers);
        $parser->setUserAgent($headers['user-agent']);

        try {
            $isMobile = $parser->isMobile();
            $isTablet = $parser->isTablet();
        } catch (\Detection\Exception\MobileDetectException $e) {
            throw new \UserAgentParserComparison\Exception\DetectionErroredException(
                'No result found for user agent: ' . $headers['user-agent'],
                0,
                $e,
            );
        }

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
                type: $isTablet ? \UaDeviceType\Type::Tablet : ($isMobile ? \UaDeviceType\Type::MobileDevice : \UaDeviceType\Type::Unknown),
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
                name: null,
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
                name: null,
                manufacturer: new \UaResult\Company\Company(
                    type: 'unknown',
                    name: null,
                    brandname: null,
                ),
                version: new \BrowserDetector\Version\NullVersion(),
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
            rawResult: [
                'isMobile' => $isMobile,
                'isTablet' => $isTablet,
            ],
            result: $resultObject,
        );
    }

    /**
     * @param array{isMobile: bool} $resultRaw
     *
     * @throws void
     */
    private function hasResult(ResultInterface $result): bool
    {
        return $result->getDevice()->getType() !== \UaDeviceType\Type::Unknown;
    }
}
