<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 20.06.2019 17:43
 */

namespace Leadvertex\External\Export\Format;


use Leadvertex\External\Export\App\Components\ApiParams;
use Leadvertex\External\Export\App\Components\GenerateParams;
use Leadvertex\External\Export\App\Components\StoredConfig;
use Leadvertex\External\Export\App\Scheme;

interface FormatterInterface
{

    public function __construct(ApiParams $apiParams, string $runtimeDir, string $outputDir);

    public function getScheme(): Scheme;

    public function isConfigValid(StoredConfig $config): bool;

    public function generate(GenerateParams $params);

}