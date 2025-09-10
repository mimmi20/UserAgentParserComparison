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

namespace UserAgentParserComparison\Model;

/**
 * Bot model
 */
final class Bot
{
    private bool $isBot         = false;
    private string | null $name = null;
    private string | null $type = null;

    /** @throws void */
    public function setIsBot(bool $mode): void
    {
        $this->isBot = $mode;
    }

    /** @throws void */
    public function getIsBot(): bool
    {
        return $this->isBot;
    }

    /** @throws void */
    public function setName(string | null $name): void
    {
        $this->name = $name;
    }

    /** @throws void */
    public function getName(): string | null
    {
        return $this->name;
    }

    /** @throws void */
    public function setType(string | null $type): void
    {
        $this->type = $type;
    }

    /** @throws void */
    public function getType(): string | null
    {
        return $this->type;
    }

    /**
     * @return array<bool>|array<null>|array<string>
     * @phpstan-return array{isBot: bool, name: string|null, type: string|null}
     *
     * @throws void
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
