<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

/**
 * Abstraction for Browscap full type
 *
 * @see https://github.com/browscap/browscap-php
 */
final class BrowscapFull extends AbstractBrowscap
{
    /**
     * Name of the provider
     */
    protected string $name = 'BrowscapFull';

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
            'isTouch' => true,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => true,
        ],
    ];
}
