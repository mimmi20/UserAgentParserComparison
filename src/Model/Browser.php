<?php
namespace UserAgentParserComparison\Model;

/**
 * Browser model
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 */
final class Browser
{
    private ?string $name = null;

    private Version $version;

    public function __construct()
    {
        $this->version = new Version();
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param Version $version
     */
    public function setVersion(Version $version): void
    {
        $this->version = $version;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    /**
     * @return string[]|array[]|null[]
     * @phpstan-return array{name: string|null, version: array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}}
     */
    public function toArray(): array
    {
        return [
            'name'    => $this->getName(),
            'version' => $this->getVersion()->toArray(),
        ];
    }
}
