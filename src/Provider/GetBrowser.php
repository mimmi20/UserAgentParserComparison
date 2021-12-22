<?php
namespace UserAgentParserComparison\Provider;

use BrowscapPHP\Browscap;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for Browscap full type
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 * @see https://github.com/browscap/browscap-php
 */
class GetBrowser extends AbstractBrowscap
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected $name = 'PHP Native get_browser';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected $homepage = '';

    /**
     * Composer package name
     *
     * @var string
     */
    protected $packageName = '';

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
            'isTouch'  => true,
        ],

        'bot' => [
            'isBot' => true,
            'name'  => true,
            'type'  => true,
        ],
    ];

    public function __construct()
    {
        // nothing to do here
    }

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        /* @var $resultRaw \stdClass */
        $resultRaw = \get_browser($userAgent, false);

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
         * Bot detection (does only work with full_php_browscap.ini)
         */
        if ($this->isBot($resultRaw) === true) {
            $this->hydrateBot($result->getBot(), $resultRaw);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw);
        $this->hydrateRenderingEngine($result->getRenderingEngine(), $resultRaw);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultRaw);
        $this->hydrateDevice($result->getDevice(), $resultRaw);

        return $result;
    }
}
