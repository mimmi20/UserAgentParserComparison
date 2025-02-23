<?php

declare(strict_types = 1);

use UserAgentParserComparison\Html\Index;

/*
 * Generate a detail page for each user agent
 */
include_once 'bootstrap.php';

echo '~~~ create html index ~~~' . PHP_EOL;

echo '.';

/** @var PDO $pdo */
$generate = new Index($pdo, 'UserAgentParserComparison comparison');

/*
 * persist!
 */
$folder = $basePath;
if (!file_exists($folder)) {
    mkdir($folder, 0777, true);
}

file_put_contents($folder . '/../index.html', $generate->getHtml());

echo PHP_EOL;
