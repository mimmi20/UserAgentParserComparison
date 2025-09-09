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

namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Model;

use function array_merge;
use function is_array;
use function mb_trim;
use function preg_match;

/**
 * Abstraction for all providers
 */
abstract class AbstractParseProvider extends AbstractProvider
{
    /**
     * Parse the given user agent and return a result if possible
     *
     * @param array<string, string> $headers
     *
     * @throws Exception\NoResultFoundException
     */
    abstract public function parse(array $headers = []): Model\UserAgent;

    /** @throws void */
    abstract public function isActive(): bool;

    /** @throws void */
    protected function isRealResult(
        string | null $value,
        string | null $group = null,
        string | null $part = null,
    ): bool {
        if ($value === null) {
            return false;
        }

        $value = mb_trim($value);

        if ($value === '') {
            return false;
        }

        $regexes = $this->defaultValues['general'];

        if (
            $group !== null
            && $part !== null
            && isset($this->defaultValues[$group][$part])
            && is_array($this->defaultValues[$group][$part])
        ) {
            $regexes = array_merge($regexes, $this->defaultValues[$group][$part]);
        }

        foreach ($regexes as $regex) {
            if (preg_match($regex, $value) === 1) {
                return false;
            }
        }

        return true;
    }

    /** @throws void */
    protected function getRealResult(
        string | null $value,
        string | null $group = null,
        string | null $part = null,
    ): string | null {
        if ($this->isRealResult($value, $group, $part) === true) {
            return $value;
        }

        return null;
    }
}
