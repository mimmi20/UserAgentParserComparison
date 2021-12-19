<?php
namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;

/**
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 * @see https://github.com/browscap/browscap-php
 */
class MobileDetect extends AbstractTestProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected $name = 'MobileDetect';

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected $homepage = 'https://github.com/serbanghita/Mobile-Detect';

    /**
     * Composer package name
     *
     * @var string
     */
    protected $packageName = 'mobiledetect/mobiledetectlib';

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
            'model'    => true,
            'brand'    => false,
            'type'     => false,
            'isMobile' => true,
            'isTouch'  => false,
        ],

        'bot' => [
            'isBot' => false,
            'name'  => false,
            'type'  => false,
        ],
    ];

    /**
     * @throws NoResultFoundException
     *
     * @return iterable
     */
    public function getTests(): iterable
    {
        return [];
    }
}
