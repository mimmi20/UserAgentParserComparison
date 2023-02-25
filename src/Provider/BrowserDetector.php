<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use BrowserDetector\Detector;
use Psr\SimpleCache\InvalidArgumentException;
use UaResult\Browser\BrowserInterface;
use UaResult\Device\DeviceInterface;
use UaResult\Engine\EngineInterface;
use UaResult\Os\OsInterface;
use UaResult\Result\ResultInterface;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Model;

use function mb_stripos;

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
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => true,
            'version' => true,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'model' => true,
            'brand' => true,
            'type' => true,
            'isMobile' => true,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => true,
        ],
    ];

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'general' => ['/^UNK$/i'],

        'bot' => [
            'name' => [
                '/^Bot$/i',
                '/^Generic Bot$/i',
            ],
        ],
    ];

    /** @throws void */
    public function __construct(private readonly Detector $parser)
    {
    }

    /**
     * @throws NoResultFoundException
     * @throws InvalidArgumentException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(
        string $userAgent,
        array $headers = [],
    ): Model\UserAgent {
        $parserResult = ($this->parser)($userAgent);

        /*
         * No result found?
         */
        if (true !== $this->hasResult($parserResult)) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($this->getResultRaw($parserResult));

        /*
         * Bot detection
         */
        if (true === $result->isBot()) {
            $this->hydrateBot($result->getBot(), $parserResult->getBrowser()->getType()->isBot());

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $parserResult->getBrowser());
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $parserResult->getEngine());
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $parserResult->getOs());
        $this->hydrateDevice($result->getDevice(), $parserResult->getDevice());

        return $result;
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return array{client: array<mixed>|string|null, operatingSystem: array<mixed>|string|null, device: array<string, mixed>, bot: array<mixed>|bool|null, extra: array<string, mixed>}
     *
     * @throws void
     */
    private function getResultRaw(ResultInterface $result): array
    {
        return [
            'client' => $result->getBrowser()->getName(),
            'operatingSystem' => $result->getOs()->getName(),

            'device' => [
                'brand' => $result->getDevice()->getBrand()->getName(),
                'brandName' => $result->getDevice()->getBrand()->getBrandName(),

                'model' => $result->getDevice()->getMarketingName(),

                'device' => $result->getDevice()->getDeviceName(),
                'deviceName' => $result->getDevice()->getMarketingName(),
            ],

            'bot' => null,

            'extra' => [
                'isBot' => $result->getBrowser()->getType()->isBot(),
            ],
        ];
    }

    /** @throws void */
    private function hasResult(ResultInterface $result): bool
    {
        if ($result->getBrowser()->getType()->isBot()) {
            return true;
        }

        $client = $result->getBrowser()->getName();

        if (null !== $client && $this->isRealResult($client)) {
            return true;
        }

        $os = $result->getOs()->getName();

        if (null !== $os && $this->isRealResult($os)) {
            return true;
        }

        $engine = $result->getEngine()->getName();

        if (null !== $engine && $this->isRealResult($engine)) {
            return true;
        }

        $device = $result->getDevice()->getDeviceName();

        return null !== $device && false === mb_stripos($device, 'general') && $this->isRealResult($device);
    }

    /**
     * @param array<string, string> $botRaw
     *
     * @throws void
     */
    private function hydrateBot(Model\Bot $bot, array $botRaw): void
    {
        $bot->setIsBot(true);

        if (isset($botRaw['name'])) {
            $bot->setName($this->getRealResult($botRaw['name'], 'bot', 'name'));
        }

        if (!isset($botRaw['category'])) {
            return;
        }

        $bot->setType($this->getRealResult($botRaw['category']));
    }

    /** @throws void */
    private function hydrateBrowser(
        Model\Browser $browser,
        BrowserInterface $clientRaw,
    ): void {
        if ($clientRaw->getName()) {
            $browser->setName($this->getRealResult($clientRaw->getName()));
        }

        if (!$clientRaw->getVersion()) {
            return;
        }

        $browser->getVersion()->setComplete($this->getRealResult($clientRaw->getVersion()->getVersion()));
    }

    /** @throws void */
    private function hydrateRenderingEngine(
        Model\RenderingEngine $engine,
        EngineInterface $clientRaw,
    ): void {
        if (!$clientRaw->getName()) {
            return;
        }

        $engine->setName($this->getRealResult($clientRaw->getName()));
    }

    /** @throws void */
    private function hydrateOperatingSystem(
        Model\OperatingSystem $os,
        OsInterface $osRaw,
    ): void {
        if ($osRaw->getName()) {
            $os->setName($this->getRealResult($osRaw->getName()));
        }

        if (!$osRaw->getVersion()) {
            return;
        }

        $os->getVersion()->setComplete($this->getRealResult($osRaw->getVersion()->getVersion()));
    }

    /** @throws void */
    private function hydrateDevice(
        Model\Device $device,
        DeviceInterface $result,
    ): void {
        $deviceName = $result->getDeviceName();

        if (null !== $deviceName && false !== mb_stripos($deviceName, 'general')) {
            $device->setModel($this->getRealResult($deviceName));
        }

        $device->setBrand($this->getRealResult($result->getBrand()->getName()));
        $device->setType($this->getRealResult($result->getType()->getName()));

        if (true === $result->getType()->isMobile()) {
            $device->setIsMobile(true);
        }

        if (true !== $result->getDisplay()->hasTouch()) {
            return;
        }

        $device->setIsTouch(true);
    }
}
