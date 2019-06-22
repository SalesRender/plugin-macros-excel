#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use Leadvertex\External\Export\App\Commands\BackgroundCommand;
use Leadvertex\External\Export\App\Commands\CleanUpCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new CleanUpCommand());
$application->add(new BackgroundCommand());
$application->run();