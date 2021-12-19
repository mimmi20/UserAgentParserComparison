<?php
namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Model;
use UserAgentParserComparison\Provider\AbstractProvider;

/**
 * Abstraction for all providers
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 */
abstract class AbstractTestProvider extends AbstractProvider
{
    /**
     * @throws Exception\NoResultFoundException
     *
     * @return iterable
     */
    abstract public function getTests(): iterable;
}
