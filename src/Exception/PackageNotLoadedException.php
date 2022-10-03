<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Exception;

use Exception;

/**
 * This is thrown if a composer package is not loaded
 */
final class PackageNotLoadedException extends Exception implements ExceptionInterface
{
}
