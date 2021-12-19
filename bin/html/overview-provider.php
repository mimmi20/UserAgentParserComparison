<?php
use UserAgentParserComparison\Html\OverviewProvider;

/*
 * Generate a page for each provider
 */
include_once 'bootstrap.php';

/* @var $pdo \PDO */

$statementSelectProvider = $pdo->prepare('SELECT * FROM `provider` WHERE `proType` = :proType');

$statementSelectProvider->bindValue(':proType', 'real', \PDO::PARAM_STR);

$statementSelectProvider->execute();

while ($dbResultProvider = $statementSelectProvider->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {

    echo $dbResultProvider['proName'] . PHP_EOL;
    
    $generate = new OverviewProvider($pdo, $dbResultProvider, 'Overview - ' . $dbResultProvider['proName']);
    
    /*
     * persist!
     */
    $folder = $basePath;
    if (! file_exists($folder)) {
        mkdir($folder, 0777, true);
    }

    file_put_contents($folder . '/' . $dbResultProvider['proName'] . '.html', $generate->getHtml());
}
