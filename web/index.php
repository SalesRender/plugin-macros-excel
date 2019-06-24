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
use Webmozart\PathUtil\Path;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Slim\App([
    'settings' => [
        'addContentLengthHeader' => true,
        'displayErrorDetails' => true,

    ],
]);

$runtimeDir = Path::canonicalize(__DIR__ . '/../runtime/');
$outputDir = Path::canonicalize(__DIR__ . '/compiled/');
$urlPattern = '/{formatter:[a-zA-Z][a-zA-Z\d_]*}';

$app->map(['CONFIG'], $urlPattern,function (Request $request, Response $response, $args) use ($runtimeDir, $outputDir) {
    $format = $args['formatter'];

    $apiParams = new ApiParams(
        $request->getParsedBodyParam('api')['token'],
        $request->getParsedBodyParam('api')['endpointUrl']
    );

    $classname = "\Leadvertex\External\Export\Format\\{$format}\\{$format}";
    /** @var FormatterInterface $formatter */
    $formatter = new $classname($apiParams, $runtimeDir, $outputDir);
    return $response->withJson(
        $formatter->getScheme()->toArray(),
        200
    );
});

$app->map(['VALIDATE'], $urlPattern,function (Request $request, Response $response, $args) use ($runtimeDir, $outputDir) {
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
    $formatter = new $classname($apiParams, $runtimeDir, $outputDir);
    if ($formatter->isConfigValid($config)) {
        return $response->withJson(['valid' => true],200);
    } else {
        return $response->withJson(['valid' => false],400);
    }
});

$app->map(['GENERATE'], $urlPattern,function (Request $request, Response $response, $args) use ($runtimeDir, $outputDir) {

    $formatter = $args['formatter'];
    $classname = "\Leadvertex\External\Export\Format\\{$formatter}\\{$formatter}";

    /** @var FormatterInterface $formatter */
    $formatter = new $classname(
        new ApiParams(
            $request->getParsedBodyParam('api')['token'],
            $request->getParsedBodyParam('api')['endpointUrl']
        ),
        $runtimeDir,
        $outputDir
    );

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

    $tokensDir = Path::canonicalize(__DIR__ . '/../runtime/tokens');
    $handler = new DeferredRunner($tokensDir);
    $handler->prepend($formatter, $params);

    $script = Path::canonicalize(__DIR__ . '/../console.php');
    $command = "php {$script} app:background {$batchToken}";;

    $isWindowsOS = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    if ($isWindowsOS) {
        pclose(popen("start /B {$command}", "r"));
    } else {
        exec("{$command} > /dev/null &");
    }

    return $response->withJson(['result' => true],200);
});

$app->run();