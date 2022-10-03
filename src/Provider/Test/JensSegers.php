<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;

/** @see https://github.com/browscap/browscap-php */
final class JensSegers extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'JenssegersAgent';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/jenssegers/agent';

    /**
     * Composer package name
     */
    protected string $packageName = 'jenssegers/agent';

    protected string $language = 'PHP';

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
            'type' => false,
            'isMobile' => true,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => false,
        ],
    ];

    /** @throws NoResultFoundException */
    public function getTests(): iterable
    {
        return [];
    }
}
