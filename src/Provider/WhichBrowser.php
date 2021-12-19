<?php
namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;
use WhichBrowser\Parser as WhichBrowserParser;

/**
 * Abstraction for whichbrowser/parser
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @author Niels Leenheer <niels@leenheer.nl>
 * @license MIT
 * @see https://github.com/WhichBrowser/Parser
 */
class WhichBrowser extends AbstractParseProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected $name = 'WhichBrowser';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected $homepage = 'https://github.com/WhichBrowser/Parser';

    /**
     * Composer package name
     *
     * @var string
     */
    protected $packageName = 'whichbrowser/parser';

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
            'type'  => false,
        ],
    ];

    /**
     * Used for unitTests mocking
     *
     * @var WhichBrowserParser
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
     * @param  array              $headers
     * @return WhichBrowserParser
     */
    public function getParser(array $headers)
    {
        if ($this->parser === null) {
            $this->parser = new WhichBrowserParser($headers);
        }

        return $this->parser;
    }

    /**
     *
     * @param Model\Bot                   $bot
     * @param \WhichBrowser\Model\Browser $browserRaw
     */
    private function hydrateBot(Model\Bot $bot, \WhichBrowser\Model\Browser $browserRaw): void
    {
        $bot->setIsBot(true);
        $bot->setName($this->getRealResult($browserRaw->getName()));
    }

    /**
     *
     * @param Model\Browser               $browser
     * @param \WhichBrowser\Model\Browser $browserRaw
     */
    private function hydrateBrowser(Model\Browser $browser, \WhichBrowser\Model\Browser $browserRaw): void
    {
        if ($this->isRealResult($browserRaw->getName(), 'browser', 'name') === true) {
            $browser->setName($browserRaw->getName());
            $browser->getVersion()->setComplete((string) $this->getRealResult($browserRaw->getVersion()));

            return;
        }

        if (isset($browserRaw->using) && $browserRaw->using instanceof \WhichBrowser\Model\Using) {
            /* @var $usingRaw \WhichBrowser\Model\Using */
            $usingRaw = $browserRaw->using;

            if ($this->isRealResult($usingRaw->getName()) === true) {
                $browser->setName($usingRaw->getName());

                $browser->getVersion()->setComplete((string) $this->getRealResult($usingRaw->getVersion()));
            }
        }
    }

    /**
     *
     * @param Model\RenderingEngine      $engine
     * @param \WhichBrowser\Model\Engine $engineRaw
     */
    private function hydrateRenderingEngine(Model\RenderingEngine $engine, \WhichBrowser\Model\Engine $engineRaw): void
    {
        $engine->setName($this->getRealResult($engineRaw->getName()));
        $engine->getVersion()->setComplete((string) $this->getRealResult($engineRaw->getVersion()));
    }

    /**
     *
     * @param Model\OperatingSystem  $os
     * @param \WhichBrowser\Model\Os $osRaw
     */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, \WhichBrowser\Model\Os $osRaw): void
    {
        $os->setName($this->getRealResult($osRaw->getName()));
        $os->getVersion()->setComplete((string) $this->getRealResult($osRaw->getVersion()));
    }

    /**
     *
     * @param Model\Device               $device
     * @param \WhichBrowser\Model\Device $deviceRaw
     * @param WhichBrowserParser         $parser
     */
    private function hydrateDevice(Model\Device $device, \WhichBrowser\Model\Device $deviceRaw, WhichBrowserParser $parser): void
    {
        $device->setModel($this->getRealResult($deviceRaw->getModel()));
        $device->setBrand($this->getRealResult($deviceRaw->getManufacturer()));
        $device->setType($this->getRealResult($parser->getType()));

        if ($parser->isMobile() === true) {
            $device->setIsMobile(true);
        }
    }

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $headers['User-Agent'] = $userAgent;

        $parser = $this->getParser($headers);

        /*
         * No result found?
         */
        if ($parser->isDetected() !== true) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($parser->toArray());

        /*
         * Bot detection
         */
        if ($parser->getType() === 'bot') {
            $this->hydrateBot($result->getBot(), $parser->browser);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $parser->browser);
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $parser->engine);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $parser->os);
        $this->hydrateDevice($result->getDevice(), $parser->device, $parser);

        return $result;
    }
}
