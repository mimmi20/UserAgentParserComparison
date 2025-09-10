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
     * @throws MobileDetectException
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

        /*
         * Since Mobile_Detect to a regex comparison on every call
         * We cache it here for all checks and hydration
         */
        $resultCache = [
            'isMobile' => $parser->isMobile(),
        ];

        /*
         * No result found?
         */
        if ($this->hasResult($resultCache) !== true) {
            throw new NoResultFoundException(
                'No result found for user agent: ' . $headers['user-agent'],
            );
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($resultCache);

        /*
         * hydrate the result
         */
        $this->hydrateDevice($result->getDevice(), $resultCache);

        return $result;
    }

    /**
     * @param array{isMobile: bool} $resultRaw
     *
     * @throws void
     */
    private function hasResult(array $resultRaw): bool
    {
        return $resultRaw['isMobile'] !== null;
    }

    /**
     * @param array{isMobile: bool} $resultRaw
     *
     * @throws void
     */
    private function hydrateDevice(Model\Device $device, array $resultRaw): void
    {
        if ($resultRaw['isMobile'] !== true) {
            return;
        }

        $device->setIsMobile(true);
    }
}
