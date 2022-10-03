<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Exception;

use Exception;

/**
 * Limitation reached
 */
final class LimitationExceededException extends Exception implements ExceptionInterface
{
}
