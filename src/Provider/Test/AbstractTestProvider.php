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

namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Provider\AbstractProvider;

/**
 * Abstraction for all providers
 */
abstract class AbstractTestProvider extends AbstractProvider
{
    /**
     * @return iterable<array<string, mixed>>
     * @phpstan-return iterable<string, array{result: array{resFilename: string, resRawResult: string, resBrowserName: string|null, resBrowserVersion: string|null, resEngineName: string|null, resEngineVersion: string|null, resOsName: string|null, resOsVersion: string|null, resDeviceModel: string|null, resDeviceBrand: string|null, resDeviceType: string|null, resDeviceIsMobile: bool|null, resDeviceIsTouch: bool|null, resBotIsBot: bool|null, resBotName: string|null, resBotType: string|null}, headers: array<non-empty-string, non-empty-string>}>
     *
     * @throws NoResultFoundException
     *
     * @api
     */
    abstract public function getTests(): iterable;
}
