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

use Override;

use function mb_stripos;
use function preg_match;

final class Version extends AbstractHarmonize
{
    /**
     * Only compare the major and minor version!
     *
     * @throws void
     */
    #[Override]
    public static function getHarmonizedValue(mixed $value): mixed
    {
        if ($value === null) {
            return $value;
        }

        preg_match('/\d+(?:\.*\d*)[1,2]*/', (string) $value, $result);

        if (!isset($result[0])) {
            return $value;
        }

        $useValue = $result[0];

        if (mb_stripos($useValue, '.') === false) {
            $useValue .= '.0';
        }

        return $useValue;
    }

    /**
     * Only compare the major and minor version!
     *
     * @param array<int|string, mixed> $values
     *
     * @return array<int|string, mixed>
     *
     * @throws void
     */
    #[Override]
    public static function getHarmonizedValues(array $values): array
    {
        foreach ($values as $key => $value) {
            $values[$key] = self::getHarmonizedValue($value);
        }

        return $values;
    }
}
