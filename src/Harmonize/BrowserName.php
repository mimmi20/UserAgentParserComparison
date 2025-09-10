<?php

/**
 * This file is part of the mimmi20/user-agent-parser-comparison package.
 *
 * Copyright (c) 2015-2025, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

final class BrowserName extends AbstractHarmonize
{
    /** @var array<string, array<int, string>> */
    protected static array $replaces = [
        '360 Browser' => ['360 Phone Browser'],

        'Amazon Silk' => ['Amazon Silk Browser'],

        'Chrome' => [
            'Chrome Dev',
            'Chrome Mobile',
        ],

        'Firefox' => ['Firefox Mobile'],

        'IE' => [
            'IE Mobile',
            'IEMobile',
            'Internet Explorer',
            'MSIE',
        ],

        'Opera' => [
            'Opera Mini',
            'Opera Mobile',
            'Opera Next',
        ],
    ];
}
