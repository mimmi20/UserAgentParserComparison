<?php

declare(strict_types = 1);

use UserAgentParserComparison\Html\OverviewProvider;

/*
 * Generate a page for each provider
 */
include_once 'bootstrap.php';

echo '~~~ create html overview for all providers ~~~' . PHP_EOL;

/** @var PDO $pdo */
$statementSelectProvider = $pdo->prepare('SELECT * FROM `real-provider`');
$statementSelectProvider->execute();

while ($dbResultProvider = $statementSelectProvider->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
    echo '.';

    $generate = new OverviewProvider($pdo, $dbResultProvider, 'Overview - ' . $dbResultProvider['proName']);

    /*
     * persist!
     */
    $folder = $basePath;
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }

    file_put_contents($folder . '/' . $dbResultProvider['proName'] . '.html', $generate->getHtml());
}

echo PHP_EOL;
