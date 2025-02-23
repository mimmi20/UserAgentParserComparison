<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

final class OsName extends AbstractHarmonize
{
    /** @var array<string, array<int, string>> */
    protected static array $replaces = [
        'BlackBerry' => ['BlackBerry OS'],

        'Chrome OS' => ['ChromeOS'],

        'iOS' => ['iPhone OS'],

        'Linux' => ['GNU/Linux'],

        'OS X' => [
            'Mac',
            'Mac OS',
            'Mac OS X',
        ],

        'Symbian' => [
            'SymbianOS',
            'Symbian OS',
            'Symbian OS Series 40',
            'Symbian OS Series 60',
            'Symbian S60',

            'Series40',
            'Series60',

            'Nokia Series 40',
        ],

        'Windows' => [
            'Win32',
            'Win2000',
            'WinVista',
            'Win7',
            'Win8',

            'Windows 2000',
            'Windows XP',
            'Windows 7',
            'Windows 8',

            'Windows CE',
            'Windows Mobile',
        ],
    ];
}
