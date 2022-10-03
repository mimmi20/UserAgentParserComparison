<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Provider\AbstractProvider;

/**
 * Abstraction for all providers
 */
abstract class AbstractTestProvider extends AbstractProvider
{
    /** @throws Exception\NoResultFoundException */
    abstract public function getTests(): iterable;
}
