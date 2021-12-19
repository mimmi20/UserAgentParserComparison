<?php
namespace UserAgentParserComparison\Provider;

use EndorphinStudio\Detector as EndorphinDetector;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for endorphin-studio/browser-detector
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 * @see https://github.com/EndorphinDetector-studio/browser-detector
 */
class Endorphin extends AbstractParseProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected $name = 'Endorphin';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected $homepage = 'https://github.com/endorphin-studio/browser-detector';

    /**
     * Composer package name
     *
     * @var string
     */
    protected $packageName = 'endorphin-studio/browser-detector';

    protected $detectionCapabilities = [

        'browser' => [
            'name'    => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name'    => false,
            'version' => false,
        ],

        'operatingSystem' => [
            'name'    => true,
            'version' => true,
        ],

        'device' => [
            'model'    => false,
            'brand'    => false,
            'type'     => true,
            'isMobile' => false,
            'isTouch'  => false,
        ],

        'bot' => [
            'isBot' => true,
            'name'  => true,
            'type'  => true,
        ],
    ];

    protected $defaultValues = [

        'general' => [
            '/^N\\\\A$/i',
        ],

        'device' => [

            'model' => [
                '/^Desktop/i',
            ],
        ],
    ];

    /**
     * Used for unitTests mocking
     *
     * @var EndorphinDetector\Detector
     */
    private $parser;

    /**
     *
     * @throws PackageNotLoadedException
     */
    public function __construct()
    {
        $this->checkIfInstalled();
    }

    /**
     *
     * @param  string                           $userAgent
     * @return EndorphinDetector\DetectorResult
     */
    public function getParser($userAgent)
    {
        if ($this->parser !== null) {
            return $this->parser;
        }

        return EndorphinDetector\Detector::analyse($userAgent);
    }

    /**
     *
     * @param EndorphinDetector\DetectorResult $resultRaw
     *
     * @return bool
     */
    private function hasResult(EndorphinDetector\DetectorResult $resultRaw): bool
    {
        if ($this->isRealResult($resultRaw->OS->getName()) === true) {
            return true;
        }

        if ($this->isRealResult($resultRaw->Browser->getName()) === true) {
            return true;
        }

        if ($this->isRealResult($resultRaw->Device->getName(), 'device', 'model') === true) {
            return true;
        }

        if ($this->isRealResult($resultRaw->Robot->getType()) === true) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param Model\Bot               $bot
     * @param EndorphinDetector\Robot $resultRaw
     */
    private function hydrateBot(Model\Bot $bot, EndorphinDetector\Robot $resultRaw): void
    {
        $bot->setIsBot(true);
        $bot->setName($this->getRealResult($resultRaw->getName()));
        $bot->setType($this->getRealResult($resultRaw->getType()));
    }

    /**
     *
     * @param Model\Browser             $browser
     * @param EndorphinDetector\Browser $resultRaw
     */
    private function hydrateBrowser(Model\Browser $browser, EndorphinDetector\Browser $resultRaw): void
    {
        $browser->setName($this->getRealResult($resultRaw->getName()));
        $browser->getVersion()->setComplete((string) $this->getRealResult($resultRaw->getVersion()));
    }

    /**
     *
     * @param Model\OperatingSystem $os
     * @param EndorphinDetector\OS  $resultRaw
     */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, EndorphinDetector\OS $resultRaw): void
    {
        $os->setName($this->getRealResult($resultRaw->getName()));
        $os->getVersion()->setComplete((string) $this->getRealResult($resultRaw->getVersion()));
    }

    /**
     *
     * @param Model\Device             $device
     * @param EndorphinDetector\Device $resultRaw
     */
    private function hydrateDevice(Model\Device $device, EndorphinDetector\Device $resultRaw): void
    {
        // $device->setModel($this->getRealResult($resultRaw->ModelName));
        $device->setType($this->getRealResult($resultRaw->getType()));
    }

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $resultRaw = $this->getParser($userAgent);

        /*
         * No result found?
         */
        if ($this->hasResult($resultRaw) !== true) {
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
        if ($this->isRealResult($resultRaw->Robot->getType()) === true) {
            $this->hydrateBot($result->getBot(), $resultRaw->Robot);

            return $result;
        }

        /*
         * hydrate the result
         */
        if ($resultRaw->Browser instanceof EndorphinDetector\Browser) {
            $this->hydrateBrowser($result->getBrowser(), $resultRaw->Browser);
        }
        if ($resultRaw->OS instanceof EndorphinDetector\OS) {
            $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw->OS);
        }
        if ($resultRaw->Device instanceof EndorphinDetector\Device) {
            $this->hydrateDevice($result->getDevice(), $resultRaw->Device);
        }

        return $result;
    }
}
