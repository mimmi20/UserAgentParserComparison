<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

final class BotName extends AbstractHarmonize
{
    protected static array $replaces = [
        'Google App Engine' => [
            'Google AppEngine',
            'AppEngine-Google',
        ],

        'Java' => ['Java Standard Library'],
    ];
}
