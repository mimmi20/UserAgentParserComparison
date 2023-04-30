<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

final class DeviceType extends AbstractHarmonize
{
    /** @var array<string, array<int, string>> */
    protected static array $replaces = [
        'camera' => ['Digital Camera'],
        'car' => [
            'car browser',
            'Car Entertainment System',
        ],

        'console' => ['gaming:console'],

        'desktop' => ['pc'],

        'ereader' => ['Ebook Reader'],

        'smartphone' => [
            'smartphone',
            'mobile:smart',
        ],

        'tv' => [
            'Smart-TV',
            'television',
            'tv',
            'TV Device',
        ],
    ];
}
