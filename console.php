#!/usr/bin/env php
<?php
use Leadvertex\Plugin\Core\Macros\Factories\AppFactory;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/medoo.php';

$factory = new AppFactory();
$application = $factory->console();
$application->run();