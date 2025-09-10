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

use function count;
use function in_array;
use function mb_trim;
use function preg_match;
use function preg_replace;
use function preg_split;

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

    /** @throws void */
    public function setMajor(int | string | null $major): void
    {
        $this->major = $major;

        $this->hydrateComplete();
    }

    /** @throws void */
    public function getMajor(): int | string | null
    {
        return $this->major;
    }

    /** @throws void */
    public function setMinor(int | string | null $minor): void
    {
        $this->minor = $minor;

        $this->hydrateComplete();
    }

    /** @throws void */
    public function getMinor(): int | string | null
    {
        return $this->minor;
    }

    /** @throws void */
    public function setPatch(int | string | null $patch): void
    {
        $this->patch = $patch;

        $this->hydrateComplete();
    }

    /** @throws void */
    public function getPatch(): int | string | null
    {
        return $this->patch;
    }

    /** @throws void */
    public function setAlias(string | null $alias): void
    {
        $this->alias = $alias;

        $this->hydrateComplete();
    }

    /** @throws void */
    public function getAlias(): string | null
    {
        return $this->alias;
    }

    /**
     * Set from the complete version string.
     *
     * @throws void
     */
    public function setComplete(string | null $complete): void
    {
        if ($complete !== null) {
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

    /** @throws void */
    public function getComplete(): string | null
    {
        return $this->complete;
    }

    /**
     * @return array<int>|array<null>|array<string>
     * @phpstan-return array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}
     *
     * @throws void
     */
    public function toArray(): array
    {
        return [
            'alias' => $this->getAlias(),

            'complete' => $this->getComplete(),
            'major' => $this->getMajor(),
            'minor' => $this->getMinor(),
            'patch' => $this->getPatch(),
        ];
    }

    /** @throws void */
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

    /** @throws void */
    private function hydrateFromComplete(string | null $complete): void
    {
        $parts = $this->getCompleteParts($complete);

        $this->setMajor($parts['major']);
        $this->setMinor($parts['minor']);
        $this->setPatch($parts['patch']);
        $this->setAlias($parts['alias']);
    }

    /**
     * @return array<int>|array<null>|array<string>
     * @phpstan-return array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}
     *
     * @throws void
     */
    private function getCompleteParts(string | null $complete): array
    {
        $versionParts = [
            'alias' => null,
            'major' => null,
            'minor' => null,
            'patch' => null,
        ];

        if ($complete !== null) {
            // only digits
            preg_match('/\d+(?:[._]*\d*)*/', $complete, $result);

            if (0 < count($result)) {
                $parts = preg_split('/[._]/', $result[0]);

                if (isset($parts[0]) && $parts[0] !== '') {
                    $versionParts['major'] = $parts[0];
                }

                if (isset($parts[1]) && $parts[1] !== '') {
                    $versionParts['minor'] = $parts[1];
                }

                if (isset($parts[2]) && $parts[2] !== '') {
                    $versionParts['patch'] = $parts[2];
                }
            }

            // grab alias
            $result = preg_split('/\d+(?:[._]*\d*)*/', $complete);

            foreach ($result as $row) {
                $row = mb_trim((string) $row);

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
