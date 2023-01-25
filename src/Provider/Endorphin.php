<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use EndorphinStudio\Detector as EndorphinDetector;
use EndorphinStudio\Detector\Data\Result;
use Throwable;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

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

    protected string $language = 'PHP';

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
            'name' => false,
            'version' => false,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'model' => false,
            'brand' => false,
            'type' => true,
            'isMobile' => false,
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
        'general' => ['/^N\\\\A$/i'],

        'device' => [
            'model' => ['/^Desktop/i'],
        ],
    ];

    /** @throws PackageNotLoadedException */
    public function __construct(private readonly EndorphinDetector\Detector $parser)
    {
        $this->checkIfInstalled();
    }

    /**
     * @throws NoResultFoundException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        try {
            $resultRaw = $this->parser->analyse($userAgent);
        } catch (Throwable $e) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent, 0, $e);
        }

        /*
         * No result found?
         */
        if (true !== $this->hasResult($resultRaw)) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($resultRaw);

        /*
         * Bot detection
         */
        if (true === $this->isRealResult($resultRaw->getRobot()->getType())) {
            $this->hydrateBot($result->getBot(), $resultRaw->getRobot());

            return $result;
        }

        /*
         * hydrate the result
         */
        if ($resultRaw->getBrowser() instanceof EndorphinDetector\Data\Browser) {
            $this->hydrateBrowser($result->getBrowser(), $resultRaw->getBrowser());
        }

        if ($resultRaw->getOs() instanceof EndorphinDetector\Data\OS) {
            $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw->getOs());
        }

        if ($resultRaw->getDevice() instanceof EndorphinDetector\Data\Device) {
            $this->hydrateDevice($result->getDevice(), $resultRaw->getDevice());
        }

        return $result;
    }

    private function hasResult(Result $resultRaw): bool
    {
        if (true === $this->isRealResult($resultRaw->getOs()->getName())) {
            return true;
        }

        if (true === $this->isRealResult($resultRaw->getBrowser()->getName())) {
            return true;
        }

        if (true === $this->isRealResult($resultRaw->getDevice()->getName(), 'device', 'model')) {
            return true;
        }

        return true === $this->isRealResult($resultRaw->getRobot()->getType());
    }

    private function hydrateBot(Model\Bot $bot, EndorphinDetector\Data\Robot $resultRaw): void
    {
        $bot->setIsBot(true);
        $bot->setName($this->getRealResult($resultRaw->getName()));
        $bot->setType($this->getRealResult($resultRaw->getType()));
    }

    private function hydrateBrowser(Model\Browser $browser, EndorphinDetector\Data\Browser $resultRaw): void
    {
        $browser->setName($this->getRealResult($resultRaw->getName()));
        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->getVersion()));
    }

    private function hydrateOperatingSystem(Model\OperatingSystem $os, EndorphinDetector\Data\Os $resultRaw): void
    {
        $os->setName($this->getRealResult($resultRaw->getName()));
        $os->getVersion()->setComplete($this->getRealResult($resultRaw->getVersion()));
    }

    private function hydrateDevice(Model\Device $device, EndorphinDetector\Data\Device $resultRaw): void
    {
        $device->setModel($this->getRealResult($resultRaw->getModel() ? $resultRaw->getModel()->getModel() : null));
        $device->setType($this->getRealResult($resultRaw->getType()));
    }
}
