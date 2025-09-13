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

use BrowserDetector\Detector;
use BrowserDetector\Version\Exception\NotNumericException;
use BrowserDetector\Version\VersionBuilder;
use Override;
use Psr\SimpleCache\InvalidArgumentException;
use UaDeviceType\Type;
use UaResult\Browser\Browser;
use UaResult\Company\Company;
use UaResult\Device\Device;
use UaResult\Device\Display;
use UaResult\Engine\Engine;
use UaResult\Os\Os;
use UaResult\Result\Result;
use UnexpectedValueException;
use UserAgentParserComparison\Exception\DetectionErroredException;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

use function mb_strtolower;
use function str_contains;

/**
 * Abstraction for mimmi20/BrowserDetector
 *
 * @see https://github.com/mimmi20/browser-detector
 */
final class BrowserDetector extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'BrowserDetector (mimmi20)';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/mimmi20/browser-detector';

    /**
     * Composer package name
     */
    protected string $packageName = 'mimmi20/browser-detector';
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
            'isTouch' => false,
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
    public function __construct(private readonly Detector $parser)
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
        try {
            /** @var array{headers: array<non-empty-string, string>, device: array{architecture: string|null, deviceName: string|null, marketingName: string|null, manufacturer: string|null, brand: string|null, dualOrientation: bool|null, simCount: int|null, display: array{width: int|null, height: int|null, touch: bool|null, size: float|null}, type: string|null, ismobile: bool, istv: bool, bits: int|null}, os: array{name: string|null, marketingName: string|null, version: string|null, manufacturer: string|null}, client: array{name: string|null, version: string|null, manufacturer: string|null, type: string|null, isbot: bool}, engine: array{name: string|null, version: string|null, manufacturer: string|null}} $parserResult */
            $parserResult = $this->parser->getBrowser($headers);
        } catch (InvalidArgumentException | UnexpectedValueException $e) {
            throw new DetectionErroredException(
                'No result found for user agent: ' . $headers['user-agent'],
                0,
                $e,
            );
        }

        try {
            $resultObject = new Result(
                headers: $headers,
                device: new Device(
                    deviceName: $parserResult['device']['deviceName'],
                    marketingName: $parserResult['device']['marketingName'],
                    manufacturer: new Company(
                        type: $parserResult['device']['manufacturer'],
                        name: $parserResult['device']['manufacturer'],
                        brandname: $parserResult['device']['manufacturer'],
                    ),
                    brand: new Company(
                        type: $parserResult['device']['brand'],
                        name: $parserResult['device']['brand'],
                        brandname: $parserResult['device']['brand'],
                    ),
                    type: Type::fromName($parserResult['device']['type']),
                    display: new Display(
                        width: $parserResult['device']['display']['width'],
                        height: $parserResult['device']['display']['height'],
                        touch: $parserResult['device']['display']['touch'],
                        size: $parserResult['device']['display']['size'],
                    ),
                    dualOrientation: $parserResult['device']['dualOrientation'],
                    simCount: $parserResult['device']['simCount'],
                ),
                os: new Os(
                    name: $parserResult['os']['name'],
                    marketingName: $parserResult['os']['marketingName'],
                    manufacturer: new Company(
                        type: $parserResult['os']['manufacturer'],
                        name: $parserResult['os']['manufacturer'],
                        brandname: $parserResult['os']['manufacturer'],
                    ),
                    version: (new VersionBuilder())->set(
                        $parserResult['os']['version'] ?? '',
                    ),
                    bits: null,
                ),
                browser: new Browser(
                    name: $parserResult['client']['name'],
                    manufacturer: new Company(
                        type: $parserResult['client']['manufacturer'],
                        name: $parserResult['client']['manufacturer'],
                        brandname: $parserResult['client']['manufacturer'],
                    ),
                    version: (new VersionBuilder())->set(
                        $parserResult['client']['version'] ?? '',
                    ),
                    type: \UaBrowserType\Type::fromName($parserResult['client']['type']),
                    bits: null,
                    modus: null,
                ),
                engine: new Engine(
                    name: $parserResult['engine']['name'],
                    manufacturer: new Company(
                        type: $parserResult['engine']['manufacturer'],
                        name: $parserResult['engine']['manufacturer'],
                        brandname: $parserResult['engine']['manufacturer'],
                    ),
                    version: (new VersionBuilder())->set(
                        $parserResult['engine']['version'] ?? '',
                    ),
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
            rawResult: $parserResult,
            result: $resultObject,
        );
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

        return $device !== null
            && str_contains(mb_strtolower($device), 'general') === false
            && $this->isRealResult($device);
    }
}
