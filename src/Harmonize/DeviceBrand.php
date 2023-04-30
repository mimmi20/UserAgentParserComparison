<?php

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
