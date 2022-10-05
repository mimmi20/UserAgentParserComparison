<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Exception;

use Exception;

/**
 * This is thrown by the Provider if not result is found.
 */
final class NoResultFoundException extends Exception implements ExceptionInterface
{
}
