<?php
namespace UserAgentParserComparison\Provider;

use EndorphinStudio\Detector as EndorphinDetector;
use EndorphinStudio\Detector\Data\Result;
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
    protected string $name = 'Endorphin';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected string $homepage = 'https://github.com/endorphin-studio/browser-detector';

    /**
     * Composer package name
     *
     * @var string
     */
    protected string $packageName = 'endorphin-studio/browser-detector';

    protected string $language = 'PHP';

    protected array $detectionCapabilities = [

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

    protected array $defaultValues = [

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
     */
    private ?EndorphinDetector\Detector $parser = null;

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
     * @return EndorphinDetector\Detector
     */
    public function getParser()
    {
        if ($this->parser === null) {
            $this->parser = new EndorphinDetector\Detector();
        }

        return $this->parser;
    }

    /**
     *
     * @param Result $resultRaw
     *
     * @return bool
     */
    private function hasResult(Result $resultRaw): bool
    {
        if ($this->isRealResult($resultRaw->getOs()->getName()) === true) {
            return true;
        }

        if ($this->isRealResult($resultRaw->getBrowser()->getName()) === true) {
            return true;
        }

        if ($this->isRealResult($resultRaw->getDevice()->getName(), 'device', 'model') === true) {
            return true;
        }

        if ($this->isRealResult($resultRaw->getRobot()->getType()) === true) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param Model\Bot               $bot
     * @param EndorphinDetector\Data\Robot $resultRaw
     */
    private function hydrateBot(Model\Bot $bot, EndorphinDetector\Data\Robot $resultRaw): void
    {
        $bot->setIsBot(true);
        $bot->setName($this->getRealResult($resultRaw->getName()));
        $bot->setType($this->getRealResult($resultRaw->getType()));
    }

    /**
     *
     * @param Model\Browser             $browser
     * @param EndorphinDetector\Data\Browser $resultRaw
     */
    private function hydrateBrowser(Model\Browser $browser, EndorphinDetector\Data\Browser $resultRaw): void
    {
        $browser->setName($this->getRealResult($resultRaw->getName()));
        $browser->getVersion()->setComplete($this->getRealResult($resultRaw->getVersion()));
    }

    /**
     *
     * @param Model\OperatingSystem $os
     * @param EndorphinDetector\Data\Os  $resultRaw
     */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, EndorphinDetector\Data\Os $resultRaw): void
    {
        $os->setName($this->getRealResult($resultRaw->getName()));
        $os->getVersion()->setComplete($this->getRealResult($resultRaw->getVersion()));
    }

    /**
     *
     * @param Model\Device             $device
     * @param EndorphinDetector\Data\Device $resultRaw
     */
    private function hydrateDevice(Model\Device $device, EndorphinDetector\Data\Device $resultRaw): void
    {
        $device->setModel($this->getRealResult($resultRaw->getModel() ? $resultRaw->getModel()->getModel() : null));
        $device->setType($this->getRealResult($resultRaw->getType()));
    }

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $parser = $this->getParser();

        $resultRaw = $parser->analyse($userAgent);

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
        if ($this->isRealResult($resultRaw->getRobot()->getType()) === true) {
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
}
