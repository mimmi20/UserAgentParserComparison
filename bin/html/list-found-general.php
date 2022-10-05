<?php

declare(strict_types = 1);

use UserAgentParserComparison\Html\SimpleList;

/**
 * Generate some general lists
 */
include_once 'bootstrap.php';

echo '~~~ create html list for all founds ~~~' . PHP_EOL;

/*
 * create the folder
 */
$folder = $basePath . '/detected/general';
if (!file_exists($folder)) {
    mkdir($folder, 0777, true);
}

/*
 * detected - browserNames
 */

/** @var PDO $pdo */
$statement = $pdo->prepare('SELECT * FROM `found-general-browser-names`');
$statement->execute();

$generate = new SimpleList($pdo, 'Detected browser names');
$generate->setElements($statement->fetchAll(PDO::FETCH_ASSOC));

file_put_contents($folder . '/browser-names.html', $generate->getHtml());
echo '.';

/*
 * detected - renderingEngines
 */
$statement = $pdo->prepare('SELECT * FROM `found-general-engine-names`');
$statement->execute();

$generate = new SimpleList($pdo, 'Detected rendering engines');
$generate->setElements($statement->fetchAll(PDO::FETCH_ASSOC));

file_put_contents($folder . '/rendering-engines.html', $generate->getHtml());
echo '.';

/*
 * detected - OSnames
 */
$statement = $pdo->prepare('SELECT * FROM `found-general-os-names`');
$statement->execute();

$generate = new SimpleList($pdo, 'Detected operating systems');
$generate->setElements($statement->fetchAll(PDO::FETCH_ASSOC));

file_put_contents($folder . '/operating-systems.html', $generate->getHtml());
echo '.';

/*
 * detected - deviceModel
 */
$statement = $pdo->prepare('SELECT * FROM `found-general-device-models`');
$statement->execute();

$generate = new SimpleList($pdo, 'Detected device models');
$generate->setElements($statement->fetchAll(PDO::FETCH_ASSOC));

file_put_contents($folder . '/device-models.html', $generate->getHtml());
echo '.';

/*
 * detected - deviceBrand
 */
$statement = $pdo->prepare('SELECT * FROM `found-general-device-brands`');
$statement->execute();

$generate = new SimpleList($pdo, 'Detected device brands');
$generate->setElements($statement->fetchAll(PDO::FETCH_ASSOC));

file_put_contents($folder . '/device-brands.html', $generate->getHtml());
echo '.';

/*
 * detected - deviceTypes
 */
$statement = $pdo->prepare('SELECT * FROM `found-general-device-types`');
$statement->execute();

$generate = new SimpleList($pdo, 'Detected device types');
$generate->setElements($statement->fetchAll(PDO::FETCH_ASSOC));

file_put_contents($folder . '/device-types.html', $generate->getHtml());
echo '.';

/*
 * detected - botNames
 */
$statement = $pdo->prepare('SELECT * FROM `found-general-bot-names`');
$statement->execute();

$generate = new SimpleList($pdo, 'Detected bot names');
$generate->setElements($statement->fetchAll(PDO::FETCH_ASSOC));

file_put_contents($folder . '/bot-names.html', $generate->getHtml());
echo '.';

/*
 * detected - botTypes
 */
$statement = $pdo->prepare('SELECT * FROM `found-general-bot-types`');
$statement->execute();

$generate = new SimpleList($pdo, 'Detected bot types');
$generate->setElements($statement->fetchAll(PDO::FETCH_ASSOC));

file_put_contents($folder . '/bot-types.html', $generate->getHtml());
