<?php
require_once __DIR__ . '/../const.php';

$app = new Leadvertex\Plugin\Export\Core\Apps\WebApplication(
    LV_EXPORT_RUNTIME_DIR,
    LV_EXPORT_PUBLIC_DIR,
    LV_EXPORT_PUBLIC_URL,
    LV_EXPORT_CONSOLE_SCRIPT,
    LV_EXPORT_DEBUG
);

$app->run();