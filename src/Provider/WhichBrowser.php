<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use Psr\Cache\CacheItemPoolInterface;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;
use WhichBrowser\Model\Browser;
use WhichBrowser\Model\Device;
use WhichBrowser\Model\Engine;
use WhichBrowser\Model\Os;
use WhichBrowser\Model\Using;
use WhichBrowser\Parser as WhichBrowserParser;

use function assert;

/**
 * Abstraction for whichbrowser/parser
 *
 * @see https://github.com/WhichBrowser/Parser
 */
final class WhichBrowser extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'WhichBrowser';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/WhichBrowser/Parser';

    /**
     * Composer package name
     */
    protected string $packageName = 'whichbrowser/parser';
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
            'type' => false,
        ],
    ];

    /** @throws PackageNotLoadedException */
    public function __construct(
        private readonly WhichBrowserParser $parser,
        private readonly CacheItemPoolInterface $cache,
    ) {
        $this->checkIfInstalled();
    }

    /** @throws NoResultFoundException */
    public function parse(
        string $userAgent,
        array $headers = [],
    ): Model\UserAgent {
        $headers['User-Agent'] = $userAgent;

        $this->parser->analyse($headers, ['cache' => $this->cache]);

        /*
         * No result found?
         */
        if (true !== $this->parser->isDetected()) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($this->parser->toArray());

        /*
         * Bot detection
         */
        if ('bot' === $this->parser->getType()) {
            $this->hydrateBot($result->getBot(), $this->parser->browser);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $this->parser->browser);
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $this->parser->engine);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $this->parser->os);
        $this->hydrateDevice($result->getDevice(), $this->parser->device, $this->parser);

        return $result;
    }

    /** @throws void */
    private function hydrateBot(Model\Bot $bot, Browser $browserRaw): void
    {
        $bot->setIsBot(true);
        $bot->setName($this->getRealResult($browserRaw->getName()));
    }

    /** @throws void */
    private function hydrateBrowser(
        Model\Browser $browser,
        Browser $browserRaw,
    ): void {
        if (true === $this->isRealResult($browserRaw->getName(), 'browser', 'name')) {
            $browser->setName($browserRaw->getName());
            $browser->getVersion()->setComplete($this->getRealResult($browserRaw->getVersion()));

            return;
        }

        if (!isset($browserRaw->using) || !($browserRaw->using instanceof Using)) {
            return;
        }

        $usingRaw = $browserRaw->using;
        assert($usingRaw instanceof Using);

        if (true !== $this->isRealResult($usingRaw->getName())) {
            return;
        }

        $browser->setName($usingRaw->getName());

        $browser->getVersion()->setComplete($this->getRealResult($usingRaw->getVersion()));
    }

    /** @throws void */
    private function hydrateRenderingEngine(
        Model\RenderingEngine $engine,
        Engine $engineRaw,
    ): void {
        $engine->setName($this->getRealResult($engineRaw->getName()));
        $engine->getVersion()->setComplete($this->getRealResult($engineRaw->getVersion()));
    }

    /** @throws void */
    private function hydrateOperatingSystem(
        Model\OperatingSystem $os,
        Os $osRaw,
    ): void {
        $os->setName($this->getRealResult($osRaw->getName()));
        $os->getVersion()->setComplete($this->getRealResult($osRaw->getVersion()));
    }

    /** @throws void */
    private function hydrateDevice(
        Model\Device $device,
        Device $deviceRaw,
        WhichBrowserParser $parser,
    ): void {
        $device->setModel($this->getRealResult($deviceRaw->getModel()));
        $device->setBrand($this->getRealResult($deviceRaw->getManufacturer()));
        $device->setType($this->getRealResult($parser->getType()));

        if (true !== $parser->isMobile()) {
            return;
        }

        $device->setIsMobile(true);
    }
}
