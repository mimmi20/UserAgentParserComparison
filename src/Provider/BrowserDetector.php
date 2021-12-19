<?php
namespace UserAgentParserComparison\Provider;

use BrowserDetector\Detector;
use UaResult\Browser\Browser;
use UaResult\Browser\BrowserInterface;
use UaResult\Device\Device;
use UaResult\Device\DeviceInterface;
use UaResult\Engine\Engine;
use UaResult\Os\Os;
use UaResult\Result\Result;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for mimmi20/BrowserDetector
 *
 * @license MIT
 * @see https://github.com/mimmi20/browser-detector
 */
class BrowserDetector extends AbstractParseProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected $name = 'BrowserDetector';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected $homepage = 'https://github.com/mimmi20/browser-detector';

    /**
     * Composer package name
     *
     * @var string
     */
    protected $packageName = 'mimmi20/browser-detector';

    protected $detectionCapabilities = [

        'browser' => [
            'name'    => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name'    => true,
            'version' => true,
        ],

        'operatingSystem' => [
            'name'    => true,
            'version' => true,
        ],

        'device' => [
            'model'    => true,
            'brand'    => true,
            'type'     => true,
            'isMobile' => true,
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
            '/^UNK$/i',
        ],

        'bot' => [
            'name' => [
                '/^Bot$/i',
                '/^Generic Bot$/i',
            ],
        ],
    ];

    /**
     *
     * @var Detector
     */
    private $parser;

    /**
     *
     * @param  Detector            $parser
     * @throws PackageNotLoadedException
     */
    public function __construct(Detector $parser)
    {
        $this->parser = $parser;
    }

    /**
     *
     * @return Detector
     */
    public function getParser(): Detector
    {
        return $this->parser;
    }

    /**
     *
     * @param Result $result
     *
     * @return array
     */
    private function getResultRaw(Result $result): array
    {
        $raw = [
            'client'          => $result->getBrowser()->getName(),
            'operatingSystem' => $result->getOs()->getName(),

            'device' => [
                'brand'     => $result->getDevice()->getBrand()->getName(),
                'brandName' => $result->getDevice()->getBrand()->getBrandName(),

                'model' => $result->getDevice()->getMarketingName(),

                'device'     => $result->getDevice()->getDeviceName(),
                'deviceName' => $result->getDevice()->getMarketingName(),
            ],

            'bot' => null,

            'extra' => [
                'isBot' => $result->getBrowser()->getType()->isBot(),

                // client
                'isBrowser'     => null,
                'isFeedReader'  => null,
                'isMobileApp'   => null,
                'isPIM'         => null,
                'isLibrary'     => null,
                'isMediaPlayer' => null,

                // deviceType
                'isCamera'              => null,
                'isCarBrowser'          => null,
                'isConsole'             => null,
                'isFeaturePhone'        => null,
                'isPhablet'             => null,
                'isPortableMediaPlayer' => null,
                'isSmartDisplay'        => null,
                'isSmartphone'          => null,
                'isTablet'              => null,
                'isTV'                  => null,

                // other special
                'isDesktop'      => null,
                'isMobile'       => null,
                'isTouchEnabled' => null,
            ],
        ];

        return $raw;
    }

    /**
     *
     * @param Result $result
     *
     * @return bool
     */
    private function hasResult(Result $result): bool
    {
        if ($result->getBrowser()->getType()->isBot()) {
            return true;
        }

        $client = $result->getBrowser()->getName();
        if (isset($client) && $this->isRealResult($client)) {
            return true;
        }

        $os = $result->getOs()->getName();
        if (isset($os) && $this->isRealResult($os)) {
            return true;
        }

        $device = $result->getDevice()->getDeviceName();
        if (isset($device) && $this->isRealResult($device)) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param Model\Bot     $bot
     * @param array|boolean $botRaw
     */
    private function hydrateBot(Model\Bot $bot, $botRaw): void
    {
        $bot->setIsBot(true);

        if (isset($botRaw['name'])) {
            $bot->setName($this->getRealResult($botRaw['name'], 'bot', 'name'));
        }
        if (isset($botRaw['category'])) {
            $bot->setType($this->getRealResult($botRaw['category']));
        }
    }

    /**
     *
     * @param Model\Browser $browser
     * @param BrowserInterface       $clientRaw
     */
    private function hydrateBrowser(Model\Browser $browser, BrowserInterface $clientRaw): void
    {
        if ($clientRaw->getName()) {
            $browser->setName($this->getRealResult($clientRaw->getName()));
        }

        if ($clientRaw->getVersion()) {
            $browser->getVersion()->setComplete($this->getRealResult($clientRaw->getVersion()->getVersion()));
        }
    }

    /**
     *
     * @param Model\RenderingEngine $engine
     * @param Engine                $clientRaw
     */
    private function hydrateRenderingEngine(Model\RenderingEngine $engine, Engine $clientRaw): void
    {
        if ($clientRaw->getName()) {
            $engine->setName($this->getRealResult($clientRaw->getName()));
        }
    }

    /**
     *
     * @param Model\OperatingSystem $os
     * @param Os                    $osRaw
     */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, Os $osRaw): void
    {
        if ($osRaw->getName()) {
            $os->setName($this->getRealResult($osRaw->getName()));
        }

        if ($osRaw->getVersion()) {
            $os->getVersion()->setComplete($this->getRealResult($osRaw->getVersion()->getVersion()));
        }
    }

    /**
     *
     * @param Model\Device $device
     * @param DeviceInterface  $result
     */
    private function hydrateDevice(Model\Device $device, DeviceInterface $result): void
    {
        $device->setModel($this->getRealResult($result->getDeviceName()));
        $device->setBrand($this->getRealResult($result->getBrand()->getName()));
        $device->setType($this->getRealResult($result->getType()->getName()));

        if ($result->getType()->isMobile() === true) {
            $device->setIsMobile(true);
        }
    }

    public function parse($userAgent, array $headers = []): Model\UserAgent
    {
        $parser = $this->getParser();

        $parserResult = $parser($userAgent);

        /*
         * No result found?
         */
        if ($this->hasResult($parserResult) !== true) {
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
        if ($result->isBot() === true) {
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
}
