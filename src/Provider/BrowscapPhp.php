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

    /**
     * Set this in each Provider implementation
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    protected array $detectionCapabilities = [
        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => false,
        ],
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'brand' => false,
            'isMobile' => true,
            'isTouch' => true,
            'model' => false,
            'type' => true,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => false,
        ],

        'renderingEngine' => [
            'name' => false,
            'version' => false,
        ],
    ];
}
