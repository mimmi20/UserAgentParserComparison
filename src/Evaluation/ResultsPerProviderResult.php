<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Evaluation;

use function explode;
use function ucfirst;

final class ResultsPerProviderResult
{
    /** @var array<mixed> */
    private array $currentValue;

    /** @var array<int, string> */
    private array $values;

    /** @var array<int|string, mixed> */
    private array $harmonizedValues;

    private string $type;
    private int $sameResultCount           = 0;
    private int $harmonizedSameResultCount = 0;

    /** @param array<mixed> $currentValue */
    public function setCurrentValue(array $currentValue): void
    {
        $this->currentValue = $currentValue;
    }

    /** @return array<mixed> */
    public function getCurrentValue(): array
    {
        return $this->currentValue;
    }

    public function setValue(string | null $value): void
    {
        if (null === $value) {
            $values = [];
        } else {
            $values = explode('~~~', $value);
        }

        $this->values = $values;
    }

    /** @return array<int, string> */
    public function getValues(): array
    {
        return $this->values;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function evaluate(): void
    {
        $this->sameResultCount           = 0;
        $this->harmonizedSameResultCount = 0;

        foreach ($this->getValues() as $value) {
            if ($this->getCurrentValue() !== $value) {
                continue;
            }

            ++$this->sameResultCount;
        }

        $class                  = $this->getHarmonizerClass();
        $harmonizedCurrentValue = $class::getHarmonizedValue($this->getCurrentValue());

        foreach ($this->getHarmonizedValues() as $value) {
            if ($harmonizedCurrentValue !== $value) {
                continue;
            }

            ++$this->harmonizedSameResultCount;
        }
    }

    public function getSameResultCount(): int
    {
        return $this->sameResultCount;
    }

    public function getHarmonizedSameResultCount(): int
    {
        return $this->harmonizedSameResultCount;
    }

    private function getHarmonizerClass(): string
    {
        return '\UserAgentParserComparison\Harmonize\\' . ucfirst($this->getType());
    }

    /** @return array<int|string, mixed> */
    private function getHarmonizedValues(): array
    {
        if (null !== $this->harmonizedValues) {
            return $this->harmonizedValues;
        }

        $class = $this->getHarmonizerClass();

        $this->harmonizedValues = $class::getHarmonizedValues($this->getValues());

        return $this->harmonizedValues;
    }
}
