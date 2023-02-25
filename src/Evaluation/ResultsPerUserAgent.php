<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Evaluation;

use function array_count_values;
use function array_unique;
use function count;
use function explode;
use function max;
use function ucfirst;

final class ResultsPerUserAgent
{
    /** @var array<int, string> */
    private array $values;

    /** @var array<int|string, mixed> */
    private array $harmonizedValues;
    private string $type;
    private int | null $foundCount;
    private int | null $foundCountUnique;
    private int | null $maxSameResultCount;
    private int | null $harmonizedFoundUnique;
    private int | null $harmonizedMaxSameResultCount;

    /** @throws void */
    public function setValue(string | null $value): void
    {
        if (null === $value) {
            $values = [];
        } else {
            $values = explode('~~~', $value);
        }

        $this->values = $values;
    }

    /**
     * @return array<int, string>
     *
     * @throws void
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /** @throws void */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /** @throws void */
    public function getType(): string
    {
        return $this->type;
    }

    /** @throws void */
    public function evaluate(): void
    {
        $this->foundCount         = count($this->getValues());
        $this->foundCountUnique   = $this->getUniqueCount($this->getValues());
        $this->maxSameResultCount = $this->getMaxSameCount($this->getValues());

        $harmonizedValues = $this->getHarmonizedValues();

        $this->harmonizedFoundUnique        = $this->getUniqueCount($harmonizedValues);
        $this->harmonizedMaxSameResultCount = $this->getMaxSameCount($harmonizedValues);
    }

    /**
     * @return array<int|string, mixed>
     *
     * @throws void
     */
    public function getUniqueHarmonizedValues(): array
    {
        return array_unique($this->getHarmonizedValues());
    }

    /** @throws void */
    public function getFoundCount(): int | null
    {
        return $this->foundCount;
    }

    /** @throws void */
    public function getFoundCountUnique(): int | null
    {
        return $this->foundCountUnique;
    }

    /** @throws void */
    public function getMaxSameResultCount(): int | null
    {
        return $this->maxSameResultCount;
    }

    /** @throws void */
    public function getHarmonizedFoundUnique(): int | null
    {
        return $this->harmonizedFoundUnique;
    }

    /** @throws void */
    public function getHarmonizedMaxSameResultCount(): int | null
    {
        return $this->harmonizedMaxSameResultCount;
    }

    /**
     * @return array<int|string, mixed>
     *
     * @throws void
     */
    private function getHarmonizedValues(): array
    {
        if (null !== $this->harmonizedValues) {
            return $this->harmonizedValues;
        }

        $class = '\UserAgentParserComparison\Harmonize\\' . ucfirst($this->getType());

        $this->harmonizedValues =  $class::getHarmonizedValues($this->getValues());

        return $this->harmonizedValues;
    }

    /** @throws void */
    private function getUniqueCount(array $values): int | null
    {
        return count(array_unique($values));
    }

    /** @throws void */
    private function getMaxSameCount(array $values): int | null
    {
        if (0 === count($values)) {
            return 0;
        }

        $count = array_count_values($values);

        return max($count);
    }
}
