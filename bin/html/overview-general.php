<?php
use UserAgentParserComparison\Html\OverviewGeneral;

/*
 * Generate a general overview
 */
include_once 'bootstrap.php';

echo '~~~ create html overview ~~~' . PHP_EOL;

echo '.';

/* @var $pdo \PDO */

$generate = new OverviewGeneral($pdo, 'UserAgentParserComparison comparison overview');

/*
 * persist!
 */
$folder = $basePath;
if (! file_exists($folder)) {
 mkdir($folder, 0777, true);
}

file_put_contents($folder . '/index.html', $generate->getHtml());

echo PHP_EOL;
