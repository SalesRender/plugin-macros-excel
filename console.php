#!/usr/bin/env php
<?php

use Leadvertex\Plugin\Core\Macros\Factories\ConsoleAppFactory;

require __DIR__ . '/vendor/autoload.php';

$factory = new ConsoleAppFactory();
$application = $factory->build();
$application->run();