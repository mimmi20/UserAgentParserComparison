<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Harmonize;

use function str_ireplace;

abstract class AbstractHarmonize
{
    /** @throws void */
    public static function getHarmonizedValue(mixed $value): mixed
    {
        if (null === $value) {
            return $value;
        }

        foreach (static::$replaces as $replace => $searches) {
            $value = str_ireplace((string) $searches, (string) $replace, (string) $value);
        }

        return $value;
    }

    /**
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
