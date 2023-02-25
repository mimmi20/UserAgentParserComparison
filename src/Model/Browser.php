<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Model;

/**
 * Browser model
 */
final class Browser
{
    private string | null $name = null;
    private Version $version;

    /** @throws void */
    public function __construct()
    {
        $this->version = new Version();
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
    public function setVersion(Version $version): void
    {
        $this->version = $version;
    }

    /** @throws void */
    public function getVersion(): Version
    {
        return $this->version;
    }

    /**
     * @return array<array>|array<null>|array<string>
     * @phpstan-return array{name: string|null, version: array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}}
     *
     * @throws void
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'version' => $this->getVersion()->toArray(),
        ];
    }
}
