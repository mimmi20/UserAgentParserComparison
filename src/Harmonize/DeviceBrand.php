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

final class DeviceBrand extends AbstractHarmonize
{
    /** @var array<string, array<int, string>> */
    protected static array $replaces = [
        'BlackBerry' => ['RIM'],

        'HCL' => ['HCLme'],
        'Sony' => [
            'Sony Ericsson',
            'SonyEricsson',
        ],

        'TechnoTrend' => ['TechnoTrend Goerler/Kathrein'],
    ];
}
