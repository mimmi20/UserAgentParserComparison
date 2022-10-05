<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

/**
 * Abstraction for Browscap php type
 *
 * @see https://github.com/browscap/browscap-php
 */
final class BrowscapPhp extends AbstractBrowscap
{
    /**
     * Name of the provider
     */
    protected string $name = 'BrowscapPhp';

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
            'version' => false,
        ],

        'device' => [
            'model' => false,
            'brand' => false,
            'type' => true,
            'isMobile' => true,
            'isTouch' => true,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => false,
        ],
    ];
}
