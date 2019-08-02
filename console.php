#!/usr/bin/env php
<?php

use Leadvertex\Plugin\Exporter\Core\Apps\ConsoleApplication;

require __DIR__ . '/const.php';
$application = new ConsoleApplication();
$application->run();