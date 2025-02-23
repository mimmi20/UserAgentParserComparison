<?php
/**
 * This file is part of the browscap-helper package.
 *
 * Copyright (c) 2015-2022, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

$finder = PhpCsFixer\Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__ . '/bin')
    ->in(__DIR__ . '/src')
    ->append([__DIR__ . '/rector.php'])
    ->append([__FILE__]);

$rules = require 'vendor/mimmi20/coding-standard/src/php-cs-fixer.config.php';

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setUsingCache(true)
    ->setFinder($finder);
