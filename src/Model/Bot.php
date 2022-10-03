<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Model;

/**
 * Bot model
 */
final class Bot
{
    private bool $isBot = false;

    private string | null $name = null;

    private string | null $type = null;

    public function setIsBot(bool $mode): void
    {
        $this->isBot = $mode;
    }

    public function getIsBot(): bool
    {
        return $this->isBot;
    }

    public function setName(string | null $name): void
    {
        $this->name = $name;
    }

    public function getName(): string | null
    {
        return $this->name;
    }

    public function setType(string | null $type): void
    {
        $this->type = $type;
    }

    public function getType(): string | null
    {
        return $this->type;
    }

    /**
     * @return bool[]|null[]|string[]
     * @phpstan-return array{isBot: bool, name: string|null, type: string|null}
     */
    public function toArray(): array
    {
        return [
            'isBot' => $this->getIsBot(),
            'name' => $this->getName(),
            'type' => $this->getType(),
        ];
    }
}
