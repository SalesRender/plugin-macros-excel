#!/usr/bin/env php
<?php
require __DIR__ . '/const.php';

$application = new Leadvertex\Plugin\Export\Core\Apps\ConsoleApplication(
    LV_EXPORT_RUNTIME_DIR,
    LV_EXPORT_PUBLIC_DIR
);
$application->run();