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
    private readonly array $harmonizedValues;
    private string $type;
    private int $sameResultCount           = 0;
    private int $harmonizedSameResultCount = 0;

    /**
     * @param array<mixed> $currentValue
     *
     * @throws void
     */
    public function setCurrentValue(array $currentValue): void
    {
        $this->currentValue = $currentValue;
    }

    /**
     * @return array<mixed>
     *
     * @throws void
     */
    public function getCurrentValue(): array
    {
        return $this->currentValue;
    }

    /** @throws void */
    public function setValue(string | null $value): void
    {
        $values = $value === null ? [] : explode('~~~', $value);

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
        $this->sameResultCount           = 0;
        $this->harmonizedSameResultCount = 0;

        $class                  = $this->getHarmonizerClass();
        $harmonizedCurrentValue = $class::getHarmonizedValue($this->getCurrentValue());

        foreach ($this->getHarmonizedValues() as $value) {
            if ($harmonizedCurrentValue !== $value) {
                continue;
            }

            ++$this->harmonizedSameResultCount;
        }
    }

    /** @throws void */
    public function getSameResultCount(): int
    {
        return $this->sameResultCount;
    }

    /** @throws void */
    public function getHarmonizedSameResultCount(): int
    {
        return $this->harmonizedSameResultCount;
    }

    /** @throws void */
    private function getHarmonizerClass(): string
    {
        return '\UserAgentParserComparison\Harmonize\\' . ucfirst($this->getType());
    }

    /**
     * @return array<int|string, mixed>
     *
     * @throws void
     */
    private function getHarmonizedValues(): array
    {
        return $this->harmonizedValues;
    }
}
