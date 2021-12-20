<?php
namespace UserAgentParserComparison\Model;

/**
 * Bot model
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 */
class Bot
{
    private bool $isBot = false;

    private ?string $name = null;

    private ?string $type = null;

    public function setIsBot(bool $mode): void
    {
        $this->isBot = $mode;
    }

    public function getIsBot(): bool
    {
        return $this->isBot;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     *
     * @return bool[]|string[]|null[]
     * @phpstan-return array{isBot: bool, name: string|null, type: string|null}
     */
    public function toArray(): array
    {
        return [
            'isBot' => $this->getIsBot(),
            'name'  => $this->getName(),
            'type'  => $this->getType(),
        ];
    }
}
