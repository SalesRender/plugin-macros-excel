<?php
use Leadvertex\External\Export\Core\Apps\WebApplication;
use Webmozart\PathUtil\Path;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new WebApplication(
    Path::canonicalize(__DIR__ . '/../runtime'),
    Path::canonicalize(__DIR__ . '/compiled'),
    Path::canonicalize(__DIR__ . '/../console.php'),
    true
);

$app->run();