<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

/**
 * Abstraction for Browscap lite type
 *
 * @see https://github.com/browscap/browscap-php
 */
final class BrowscapLite extends AbstractBrowscap
{
    /**
     * Name of the provider
     */
    protected string $name = 'BrowscapLite';

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
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => false,
            'name' => false,
            'type' => false,
        ],
    ];
}
