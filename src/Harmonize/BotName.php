<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

final class BotName extends AbstractHarmonize
{
    /** @var array<string, array<int, string>> */
    protected static array $replaces = [
        'Google App Engine' => [
            'Google AppEngine',
            'AppEngine-Google',
        ],

        'Java' => ['Java Standard Library'],
    ];
}
