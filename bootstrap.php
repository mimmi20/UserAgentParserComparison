<?php
// bootstrap.php

require_once 'vendor/autoload.php';

/*
 * General settings
 */
error_reporting(E_ALL & ~ E_DEPRECATED);
ini_set('display_errors', 1);

set_time_limit(- 1);
ini_set('memory_limit', '1024M');

include 'config.php';

/** @var array $conn */

/*
 * Doctrine
 */
$isDevMode = true;
