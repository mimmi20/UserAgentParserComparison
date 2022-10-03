<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

final class BotType extends AbstractHarmonize
{
    protected static array $replaces = [
        'RSS' => [
            'Feed Fetcher',
            'Feed Parser',
        ],

        'Site Monitor' => ['Site Monitors'],
    ];
}
