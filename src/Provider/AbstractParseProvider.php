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

    protected function isRealResult(string | null $value, string | null $group = null, string | null $part = null): bool
    {
        if (null === $value) {
            return false;
        }

        $value = trim($value);

        if ('' === $value) {
            return false;
        }

        $regexes = $this->defaultValues['general'];

        if (null !== $group && null !== $part && isset($this->defaultValues[$group][$part])) {
            $regexes = array_merge($regexes, $this->defaultValues[$group][$part]);
        }

        foreach ($regexes as $regex) {
            if (1 === preg_match($regex, $value)) {
                return false;
            }
        }

        return true;
    }

    protected function getRealResult(string | null $value, string | null $group = null, string | null $part = null): string | null
    {
        if (true === $this->isRealResult($value, $group, $part)) {
            return $value;
        }

        return null;
    }
}
