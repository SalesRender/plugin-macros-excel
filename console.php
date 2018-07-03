#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new \Leadvertex\External\Export\App\Commands\CleanUpCommand());
$application->run();