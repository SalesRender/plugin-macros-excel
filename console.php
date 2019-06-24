#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use Leadvertex\External\Export\Core\Apps\ConsoleApplication;
use Webmozart\PathUtil\Path;

$application = new ConsoleApplication(
    Path::canonicalize(__DIR__ . '/runtime'),
    Path::canonicalize(__DIR__ . '/web/compiled')
);
$application->run();