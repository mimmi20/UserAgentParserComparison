<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

final class DeviceBrand extends AbstractHarmonize
{
    protected static array $replaces = [
        'Sony' => [
            'Sony Ericsson',
            'SonyEricsson',
        ],

        'BlackBerry' => ['RIM'],

        'HCL' => ['HCLme'],

        'TechnoTrend' => ['TechnoTrend Goerler/Kathrein'],
    ];
}
