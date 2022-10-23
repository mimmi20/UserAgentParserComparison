<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

final class DeviceType extends AbstractHarmonize
{
    /** @var array<string, array<int, string>> */
    protected static array $replaces = [
        'car' => [
            'car browser',
            'Car Entertainment System',
        ],

        'camera' => ['Digital Camera'],

        'console' => ['gaming:console'],

        'desktop' => ['pc'],

        'ereader' => ['Ebook Reader'],

        'tv' => [
            'Smart-TV',
            'television',
            'tv',
            'TV Device',
        ],

        'smartphone' => [
            'smartphone',
            'mobile:smart',
        ],
    ];
}
