#!/usr/bin/env php
<?php

use Leadvertex\Plugin\Handler\Factories\AppFactory;

require __DIR__ . '/const.php';

$factory = new AppFactory();
$application = $factory->console();
$application->run();