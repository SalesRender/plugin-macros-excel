<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 15:18
 * @author Timur Kasumov aka XAKEPEHOK
 */

require_once __DIR__ . '/../vendor/autoload.php';

$format = 'Excel';
$lang = 'ru';

require_once __DIR__ . "/../formats/{$format}/{$format}.php";

/** @var \Leadvertex\External\Exports\ExportDefinition $export */
$export = new $format();
echo json_encode($export->toArray($lang), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);