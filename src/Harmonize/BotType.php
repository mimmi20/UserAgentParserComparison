<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

final class BotType extends AbstractHarmonize
{
    /** @var array<string, array<int, string>> */
    protected static array $replaces = [
        'RSS' => [
            'Feed Fetcher',
            'Feed Parser',
        ],

        'Site Monitor' => ['Site Monitors'],
    ];
}
