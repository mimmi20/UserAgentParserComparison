<?php
namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for all providers
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 */
abstract class AbstractParseProvider extends AbstractProvider
{
    /**
     *
     * @param  string|null  $value
     * @param  string|null  $group
     * @param  string|null  $part
     * @return boolean
     */
    protected function isRealResult(?string $value, ?string $group = null, ?string $part = null): bool
    {
        if (null === $value) {
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

    protected function getRealResult(?string $value, ?string $group = null, ?string $part = null): ?string
    {
        if ($this->isRealResult($value, $group, $part) === true) {
            return $value;
        }

        return null;
    }

    /**
     * Parse the given user agent and return a result if possible
     *
     * @param string $userAgent
     * @param array  $headers
     *
     * @throws Exception\NoResultFoundException
     *
     * @return Model\UserAgent
     */
    abstract public function parse(string $userAgent, array $headers = []): Model\UserAgent;
}
