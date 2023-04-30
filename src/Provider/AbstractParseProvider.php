<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Model;

use function array_merge;
use function preg_match;
use function trim;

/**
 * Abstraction for all providers
 */
abstract class AbstractParseProvider extends AbstractProvider
{
    /**
     * Parse the given user agent and return a result if possible
     *
     * @throws Exception\NoResultFoundException
     */
    abstract public function parse(string $userAgent, array $headers = []): Model\UserAgent;

    /** @throws void */
    protected function isRealResult(
        string | null $value,
        string | null $group = null,
        string | null $part = null,
    ): bool {
        if ($value === null) {
            return false;
        }

        $value = trim($value);

        if ($value === '') {
            return false;
        }

        $regexes = $this->defaultValues['general'];

        if ($group !== null && $part !== null && isset($this->defaultValues[$group][$part])) {
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
