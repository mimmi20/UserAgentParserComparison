<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

use function mb_stripos;
use function preg_match;

final class Version extends AbstractHarmonize
{
    /**
     * Only compare the major and minor version!
     *
     * @throws void
     */
    public static function getHarmonizedValue(mixed $value): mixed
    {
        if (null === $value) {
            return $value;
        }

        preg_match('/\d+(?:\.*\d*)[1,2]*/', $value, $result);

        if (!isset($result[0])) {
            return $value;
        }

        $useValue = $result[0];

        if (false === mb_stripos($useValue, '.')) {
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
    public static function getHarmonizedValues(array $values): array
    {
        foreach ($values as $key => $value) {
            $values[$key] = self::getHarmonizedValue($value);
        }

        return $values;
    }
}
