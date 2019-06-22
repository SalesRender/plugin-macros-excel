<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 15:18
 * @author Timur Kasumov aka XAKEPEHOK
 */

use Leadvertex\External\Export\App\Components\ApiParams;
use Leadvertex\External\Export\App\Components\BatchParams;
use Leadvertex\External\Export\App\Components\ChunkedIds;
use Leadvertex\External\Export\App\Components\DeferredRunner;
use Leadvertex\External\Export\App\Components\StoredConfig;
use Leadvertex\External\Export\App\Components\GenerateParams;
use Leadvertex\External\Export\Format\FormatterInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Symfony\Component\Process\Process;

require_once __DIR__ . '/../vendor/autoload.php';

$format = 'Excel';
$lang = 'ru';

$app = new Slim\App([
    'settings' => [
        'addContentLengthHeader' => true,
        'displayErrorDetails' => true,
    ],
]);

$app->add(function (Request $request, Response $response, callable $next) {
    $timezone = $request->getQueryParam('timezone', 'UTC');
    if (!in_array($timezone, timezone_identifiers_list())) {
        throw new InvalidArgumentException('Trying to select unavailable timezone');
    }
    date_default_timezone_set($timezone);
    $response = $next($request, $response);
    return $response;
});

$urlPattern = '/{formatter:[a-zA-Z][a-zA-Z\d_]*}';

$app->map(['CONFIG'], $urlPattern,function (Request $request, Response $response, $args) {
    $format = $args['formatter'];

    $apiParams = new ApiParams(
        $request->getParsedBodyParam('api')['token'],
        $request->getParsedBodyParam('api')['endpointUrl']
    );

    $classname = "\Leadvertex\External\Export\Format\\{$format}\\{$format}";
    /** @var FormatterInterface $formatter */
    $formatter = new $classname($apiParams);
    return $response->withJson(
        $formatter->getScheme()->toArray(),
        200
    );
});

$app->map(['VALIDATE'], $urlPattern,function (Request $request, Response $response, $args) {
    $formatter = $args['formatter'];

    $apiParams = new ApiParams(
        $request->getParsedBodyParam('api')['token'],
        $request->getParsedBodyParam('api')['endpointUrl']
    );

    $config = new StoredConfig(
        $request->getParsedBodyParam('config')
    );

    $classname = "\Leadvertex\External\Export\Format\\{$formatter}\\{$formatter}";
    /** @var FormatterInterface $formatter */
    $formatter = new $classname($apiParams);
    if ($formatter->isConfigValid($config)) {
        return $response->withJson(['valid' => true],200);
    } else {
        return $response->withJson(['valid' => false],400);
    }
});

$app->map(['GENERATE'], $urlPattern,function (Request $request, Response $response, $args) {

    $formatter = $args['formatter'];
    $classname = "\Leadvertex\External\Export\Format\\{$formatter}\\{$formatter}";

    /** @var FormatterInterface $formatter */
    $formatter = new $classname(new ApiParams(
        $request->getParsedBodyParam('api')['token'],
        $request->getParsedBodyParam('api')['endpointUrl']
    ));

    $batchToken = $request->getParsedBodyParam('batch')['token'];
    $params = new GenerateParams(
        new StoredConfig($request->getParsedBodyParam('config')),
        new BatchParams(
            $batchToken,
            $request->getParsedBodyParam('batch')['successWebhookUrl'],
            $request->getParsedBodyParam('batch')['failsWebhookUrl'],
            $request->getParsedBodyParam('batch')['resultWebhookUrl'],
            $request->getParsedBodyParam('batch')['errorWebhookUrl']
        ),
        new ChunkedIds($request->getParsedBodyParam('ids'))
    );

    $tokensDir = __DIR__ . implode(DIRECTORY_SEPARATOR, ['..', 'runtime', 'tokens']);
    $handler = new DeferredRunner($tokensDir);
    $handler->prepend($formatter, $params);

    $command = 'php ' . __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "console.php app:background {$batchToken}";
//    $process = new Process([$command]);
//    $process->start();
    exec($command);

    return $response->withJson(['result' => true],200);
});

$app->run();