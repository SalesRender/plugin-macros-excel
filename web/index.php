<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 15:18
 * @author Timur Kasumov aka XAKEPEHOK
 */

require_once __DIR__ . '/../vendor/autoload.php';

$format = 'Excel';
$lang = 'ru';

$app = new Slim\App([
    'settings' => [
        'addContentLengthHeader' => true,
        'displayErrorDetails' => true,
    ],
]);

$app->add(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) {
    $timezone = $request->getQueryParam('timezone', 'UTC');
    if (!in_array($timezone, timezone_identifiers_list())) {
        throw new \InvalidArgumentException('Trying to select unavailable timezone');
    }
    date_default_timezone_set($timezone);
    $response = $next($request, $response);
    return $response;
});

$app->get('/', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {
    $lang = $request->getQueryParam('lang', 'en');
    $formats = [];
    $directoryIterator = new DirectoryIterator(__DIR__ . '/../formats/');
    foreach ($directoryIterator as $info) {
        if ($info->isDir() && !$info->isDot()) {
            $name = $info->getBasename();
            $classname = "\Leadvertex\External\Export\Format\\{$name}\\{$name}";
            /** @var \Leadvertex\External\Export\Format\Excel\Excel $object */
            $object = new $classname();
            $formats[$name] = $object->toArray($lang);
        }
    }
    return $response->withJson($formats);
});

$app->run();