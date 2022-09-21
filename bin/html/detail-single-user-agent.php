<?php
use UserAgentParserComparison\Html\UserAgentDetail;

/*
 * Generate a detail page for each user agent
 */
include_once 'bootstrap.php';

echo '~~~ create html list for all useragents ~~~' . PHP_EOL;

/* @var $pdo \PDO */

$statementSelectUa = $pdo->prepare('SELECT * FROM `useragent`');
$statementSelectUa->execute();

$statementSelectResults = $pdo->prepare('SELECT `result`.*, `provider`.* FROM `result` INNER JOIN `provider` ON `result`.`provider_id` = `provider`.`proId` WHERE `result`.`userAgent_id` = :uaId ORDER BY `provider`.`proName`');

while ($dbResultUa = $statementSelectUa->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
    $statementSelectResults->bindValue(':uaId', $dbResultUa['uaId'], \PDO::PARAM_STR);
    $statementSelectResults->execute();
    $results = $statementSelectResults->fetchAll(\PDO::FETCH_ASSOC);
    
    if (count($results) === 0) {
        throw new \Exception('no results found... SELECT `result`.*, `provider`.* FROM `result` INNER JOIN `provider` ON `result`.`provider_id` = `provider`.`proId` WHERE `result`.`userAgent_id` = ' . $dbResultUa['uaId']);
    }
    
    $generate = new UserAgentDetail($pdo, 'User agent detail - ' . $dbResultUa['uaString']);
    $generate->setUserAgent($dbResultUa);
    $generate->setResults($results);
    
    /*
     * create the folder
     */
    $folder = $basePath . '/user-agent-detail/' . substr($dbResultUa['uaId'], 0, 2) . '/' . substr($dbResultUa['uaId'], 2, 2);
    if (! file_exists($folder)) {
        mkdir($folder, 0777, true);
    }
    
    /*
     * persist!
     */
    file_put_contents($folder . '/' . $dbResultUa['uaId'] . '.html', $generate->getHtml());
    
    echo '.';
}
