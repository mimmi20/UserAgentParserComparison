<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Model;

use function count;
use function in_array;
use function preg_match;
use function preg_replace;
use function preg_split;
use function trim;

/**
 * Version model
 */
final class Version
{
    private int | string | null $major = '';
    private int | string | null $minor = '';
    private int | string | null $patch = '';
    private string | null $alias       = null;
    private string | null $complete    = null;

    /** @var array<int, string> */
    private static array $notAllowedAlias = [
        'a',
        'alpha',
        'prealpha',

        'b',
        'beta',
        'prebeta',

        'rc',
    ];

    public function setMajor(int | string | null $major): void
    {
        $this->major = $major;

        $this->hydrateComplete();
    }

    public function getMajor(): int | string | null
    {
        return $this->major;
    }

    public function setMinor(int | string | null $minor): void
    {
        $this->minor = $minor;

        $this->hydrateComplete();
    }

    public function getMinor(): int | string | null
    {
        return $this->minor;
    }

    public function setPatch(int | string | null $patch): void
    {
        $this->patch = $patch;

        $this->hydrateComplete();
    }

    public function getPatch(): int | string | null
    {
        return $this->patch;
    }

    public function setAlias(string | null $alias): void
    {
        $this->alias = $alias;

        $this->hydrateComplete();
    }

    public function getAlias(): string | null
    {
        return $this->alias;
    }

    /**
     * Set from the complete version string.
     */
    public function setComplete(string | null $complete): void
    {
        if (null !== $complete) {
            // check if the version has only 0 -> so no real result
            // maybe move this out to the Providers itself?
            $left = preg_replace('/[0._]/', '', $complete);
            if ('' === $left) {
                $complete = null;
            }
        }

        $this->hydrateFromComplete($complete);

        $this->complete = $complete;
    }

    public function getComplete(): string | null
    {
        return $this->complete;
    }

    /**
     * @return int[]|null[]|string[]
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
        if (null === $this->getMajor() && null === $this->getAlias()) {
            return;
        }

        $version = $this->getMajor();

        if (null !== $this->getMinor()) {
            $version .= '.' . $this->getMinor();
        }

        if (null !== $this->getPatch()) {
            $version .= '.' . $this->getPatch();
        }

        if (null !== $this->getAlias()) {
            $version = $this->getAlias() . ' - ' . $version;
        }

        $this->complete = $version;
    }

    private function hydrateFromComplete(string | null $complete): void
    {
        $parts = $this->getCompleteParts($complete);

        $this->setMajor($parts['major']);
        $this->setMinor($parts['minor']);
        $this->setPatch($parts['patch']);
        $this->setAlias($parts['alias']);
    }

    /**
     * @return int[]|null[]|string[]
     * @phpstan-return array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}
     */
    private function getCompleteParts(string | null $complete): array
    {
        $versionParts = [
            'major' => null,
            'minor' => null,
            'patch' => null,
            'alias' => null,
        ];

        if (null !== $complete) {
            // only digits
            preg_match('/\d+(?:[._]*\d*)*/', $complete, $result);

            if (0 < count($result)) {
                $parts = preg_split('/[._]/', $result[0]);

                if (isset($parts[0]) && '' !== $parts[0]) {
                    $versionParts['major'] = $parts[0];
                }

                if (isset($parts[1]) && '' !== $parts[1]) {
                    $versionParts['minor'] = $parts[1];
                }

                if (isset($parts[2]) && '' !== $parts[2]) {
                    $versionParts['patch'] = $parts[2];
                }
            }

            // grab alias
            $result = preg_split('/\d+(?:[._]*\d*)*/', $complete);

            foreach ($result as $row) {
                $row = trim($row);

                if ('' === $row) {
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
