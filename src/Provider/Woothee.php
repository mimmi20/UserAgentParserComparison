<?php
namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;
use Woothee\Classifier;
use Woothee\DataSet;

/**
 * Abstraction for woothee/woothee
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 * @see https://github.com/woothee/woothee-php
 */
class Woothee extends AbstractParseProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected string $name = 'Woothee';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected string $homepage = 'https://github.com/woothee/woothee-php';

    /**
     * Composer package name
     *
     * @var string
     */
    protected string $packageName = 'woothee/woothee';

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
            'name'    => false,
            'version' => false,
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
            'type'  => false,
        ],
    ];

    protected array $defaultValues = [

        'general' => [
            '/^UNKNOWN$/i',
        ],

        'device' => [
            'type' => [
                '/^misc$/i',
            ],
        ],

        'bot' => [
            'name' => [
                '/^misc crawler$/i',
            ],
        ],
    ];

    private ?Classifier $parser = null;

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
     * @return Classifier
     */
    public function getParser(): Classifier
    {
        if ($this->parser !== null) {
            return $this->parser;
        }

        $this->parser = new Classifier();

        return $this->parser;
    }

    /**
     *
     * @param array $resultRaw
     *
     * @return bool
     */
    private function hasResult(array $resultRaw): bool
    {
        if (isset($resultRaw['category']) && $this->isRealResult($resultRaw['category'], 'device', 'type')) {
            return true;
        }

        if (isset($resultRaw['name']) && $this->isRealResult($resultRaw['name'])) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param  array   $resultRaw
     * @return boolean
     */
    private function isBot(array $resultRaw): bool
    {
        if (isset($resultRaw['category']) && $resultRaw['category'] === DataSet::DATASET_CATEGORY_CRAWLER) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param Model\Bot $bot
     * @param array     $resultRaw
     */
    private function hydrateBot(Model\Bot $bot, array $resultRaw): void
    {
        $bot->setIsBot(true);

        if (isset($resultRaw['name'])) {
            $bot->setName($this->getRealResult($resultRaw['name'], 'bot', 'name'));
        }
    }

    /**
     *
     * @param Model\Browser $browser
     * @param array         $resultRaw
     */
    private function hydrateBrowser(Model\Browser $browser, array $resultRaw): void
    {
        if (isset($resultRaw['name'])) {
            $browser->setName($this->getRealResult($resultRaw['name']));
        }

        if (isset($resultRaw['version'])) {
            $browser->getVersion()->setComplete($this->getRealResult($resultRaw['version']));
        }
    }

    /**
     *
     * @param Model\Device $device
     * @param array        $resultRaw
     */
    private function hydrateDevice(Model\Device $device, array $resultRaw): void
    {
        if (isset($resultRaw['category'])) {
            $device->setType($this->getRealResult($resultRaw['category'], 'device', 'type'));
        }
    }

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $parser = $this->getParser();

        $resultRaw = $parser->parse($userAgent);

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
        if ($this->isBot($resultRaw) === true) {
            $this->hydrateBot($result->getBot(), $resultRaw);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw);
        // renderingEngine not available
        // operatingSystem filled OS is mixed! Examples: iPod, iPhone, Android...
        $this->hydrateDevice($result->getDevice(), $resultRaw);

        return $result;
    }
}
