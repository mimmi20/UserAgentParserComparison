<?php
namespace UserAgentParserComparison\Model;

/**
 * Version model
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 */
final class Version
{
    /**
     * @var int|string
     */
    private $major = '';

    /**
     * @var int|string|null
     */
    private $minor = '';

    /**
     * @var int|string|null
     */
    private $patch = '';

    private ?string $alias = null;

    private ?string $complete = null;

    private static array $notAllowedAlias = [
        'a',
        'alpha',
        'prealpha',

        'b',
        'beta',
        'prebeta',

        'rc',
    ];

    /**
     * @param int|string|null $major
     * @return void
     */
    public function setMajor($major): void
    {
        $this->major = $major;

        $this->hydrateComplete();
    }

    /**
     * @return int|string|null
     */
    public function getMajor()
    {
        return $this->major;
    }

    /**
     * @param int|string|null $minor
     * @return void
     */
    public function setMinor($minor): void
    {
        $this->minor = $minor;

        $this->hydrateComplete();
    }

    /**
     * @return int|string|null
     */
    public function getMinor()
    {
        return $this->minor;
    }

    /**
     * @param int|string|null $patch
     * @return void
     */
    public function setPatch($patch): void
    {
        $this->patch = $patch;

        $this->hydrateComplete();
    }

    /**
     * @return int|string|null
     */
    public function getPatch()
    {
        return $this->patch;
    }

    public function setAlias(?string $alias): void
    {
        $this->alias = $alias;

        $this->hydrateComplete();
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Set from the complete version string.
     */
    public function setComplete(?string $complete): void
    {
        if (null !== $complete) {
            // check if the version has only 0 -> so no real result
            // maybe move this out to the Providers itself?
            $left = preg_replace('/[0._]/', '', $complete);
            if ($left === '') {
                $complete = null;
            }
        }

        $this->hydrateFromComplete($complete);

        $this->complete = $complete;
    }

    public function getComplete(): ?string
    {
        return $this->complete;
    }

    /**
     * @return int[]|string[]|null[]
     * @phpstan-return array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}
     */
    public function toArray(): array
    {
        return [
            'major' => $this->getMajor(),
            'minor' => $this->getMinor(),
            'patch' => $this->getPatch(),

            'alias' => $this->getAlias(),

            'complete' => $this->getComplete(),
        ];
    }

    private function hydrateComplete(): void
    {
        if ($this->getMajor() === null && $this->getAlias() === null) {
            return;
        }

        $version = $this->getMajor();

        if ($this->getMinor() !== null) {
            $version .= '.' . $this->getMinor();
        }

        if ($this->getPatch() !== null) {
            $version .= '.' . $this->getPatch();
        }

        if ($this->getAlias() !== null) {
            $version = $this->getAlias() . ' - ' . $version;
        }

        $this->complete = $version;
    }

    private function hydrateFromComplete(?string $complete): void
    {
        $parts = $this->getCompleteParts($complete);

        $this->setMajor($parts['major']);
        $this->setMinor($parts['minor']);
        $this->setPatch($parts['patch']);
        $this->setAlias($parts['alias']);
    }

    /**
     *
     * @return int[]|string[]|null[]
     * @phpstan-return array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}
     */
    private function getCompleteParts(?string $complete): array
    {
        $versionParts = [
            'major' => null,
            'minor' => null,
            'patch' => null,
            'alias' => null,
        ];

        if (null !== $complete) {
            // only digits
            preg_match("/\d+(?:[._]*\d*)*/", $complete, $result);

            if (count($result) > 0) {
                $parts = preg_split("/[._]/", $result[0]);

                if (isset($parts[0]) && $parts[0] != '') {
                    $versionParts['major'] = $parts[0];
                }
                if (isset($parts[1]) && $parts[1] != '') {
                    $versionParts['minor'] = $parts[1];
                }
                if (isset($parts[2]) && $parts[2] != '') {
                    $versionParts['patch'] = $parts[2];
                }
            }

            // grab alias
            $result = preg_split("/\d+(?:[._]*\d*)*/", $complete);

            foreach ($result as $row) {
                $row = trim($row);

                if ($row === '') {
                    continue;
                }

                // do not use beta and other things
                if (in_array($row, self::$notAllowedAlias, true)) {
                    continue;
                }

                $versionParts['alias'] = $row;
            }
        }

        return $versionParts;
    }
}
