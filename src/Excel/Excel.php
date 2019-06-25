<?php
/**
 * Created for lv-exports.
 * Datetime: 03.07.2018 12:53
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Export\Format\Excel;


use Adbar\Dot;
use Leadvertex\External\Export\Core\Components\ApiParams;
use Leadvertex\External\Export\Core\Components\BatchResult\BatchResultInterface;
use Leadvertex\External\Export\Core\Components\Developer;
use Leadvertex\External\Export\Core\Components\GenerateParams;
use Leadvertex\External\Export\Core\Components\StoredConfig;
use Leadvertex\External\Export\Core\Components\WebhookManager;
use Leadvertex\External\Export\Core\FieldDefinitions\ArrayDefinition;
use Leadvertex\External\Export\Core\FieldDefinitions\CheckboxDefinition;
use Leadvertex\External\Export\Core\FieldDefinitions\DropdownDefinition;
use Leadvertex\External\Export\Core\Formatter\FormatterInterface;
use Leadvertex\External\Export\Core\Formatter\Scheme;
use Leadvertex\External\Export\Core\Formatter\Type;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Softonic\GraphQL\Client;
use Softonic\GraphQL\ClientBuilder;
use Webmozart\PathUtil\Path;

class Excel implements FormatterInterface
{

    /** @var Scheme */
    private $scheme;
    /**
     * @var ApiParams
     */
    private $apiParams;
    /**
     * @var string
     */
    private $runtimeDir;
    /**
     * @var string
     */
    private $outputDir;

    public function __construct(ApiParams $apiParams, string $runtimeDir, string $outputDir)
    {
        $this->apiParams = $apiParams;
        $this->runtimeDir = $runtimeDir;
        $this->outputDir = $outputDir;
    }

    public function getScheme(): Scheme
    {
        if (!$this->scheme) {
            $fields = $this->getFields();
            $this->scheme = new Scheme(
                new Developer('LeadVertex', 'support@leadvertex.com', 'exports.leadvertex.com'),
                new Type(Type::ORDERS),
                ['Excel'],
                [
                    'en' => 'Export orders to excel file',
                    'ru' => 'Выгружает заказы в excel файл',
                ],
                [
                    'fields' => new ArrayDefinition(
                        [
                            'en' => 'Fields to export',
                            'ru' => 'Поля для выгрузки',
                        ],
                        [
                            'en' => 'Fields with this order will be exported to excel table',
                            'ru' => 'Поля будут выгружены в таблицу excel в заданной последовательности',
                        ],
                        ['id', 'project.id', 'project.name', 'status.id', 'status.name'],
                        true,
                        $fields
                    ),
                    'format' => new DropdownDefinition(
                        [
                            'en' => 'File format',
                            'ru' => 'Формат файла',
                        ],
                        [
                            'en' => 'csv - simple plain-text format, xls - old excel 2003 format, xlsx - new excel format',
                            'ru' => 'csv - простой текстовый формат, xls - формат excel 2003, xlsx - новый формат excel',
                        ],
                        [
                            'csv' => [
                                'en' => '*.csv - simple plain text format',
                                'ru' => '*.csv - простой текстовый формат',
                            ],
                            'xls' => [
                                'en' => '*.xls - Excel 2003',
                                'ru' => '*.xls - Формат Excel 2003',
                            ],
                            'xlsx' => [
                                'en' => '*.xls - Excel 2007 and newer',
                                'ru' => '*.xls - Формат Excel 2007 и новее',
                            ],
                        ],
                        'csv',
                        true
                    ),
                    'headers' => new CheckboxDefinition(
                        [
                            'en' => 'Column names',
                            'ru' => 'Названия колонок',
                        ],
                        [
                            'en' => 'Add column names at first wor',
                            'ru' => 'Добавлять названия колонок на первой строчке',
                        ],
                        true,
                        true
                    )
                ]
            );
        }
        return $this->scheme;
    }

    public function isConfigValid(StoredConfig $config): bool
    {
        if (!$config->has('fields')) {
            return false;
        }

        $fields = $config->get('fields', []);
        if (!empty(array_diff($fields, $this->getFields()))) {
            return false;
        }

        $useHeaders = $config->get(
            'headers',
            $this->getScheme()->getField('headers')->getDefaultValue()
        );

        if (!is_bool($useHeaders)) {
            return false;
        }

        $defaultFormat = $this->getScheme()->getField('format')->getDefaultValue();
        $format = $config->get('format', $defaultFormat);
        if (!in_array($format, ['csv', 'xls', 'xlsx'])) {
            return false;
        }

        return true;
    }

    public function generate(GenerateParams $params)
    {
        $defaultFormat = $this->getScheme()->getField('format')->getDefaultValue();
        $format = $params->getConfig()->get('format', $defaultFormat);
        $prefix = $params->getBatchParams()->getToken();
        $filePath = Path::canonicalize("{$this->outputDir}/{$prefix}.{$format}");
        switch ($format) {
            case 'csv':
                $csv = fopen($filePath, 'w');

                $useHeaders = $params->getConfig()->get(
                    'headers',
                    $this->getScheme()->getField('headers')->getDefaultValue()
                );

                if ($useHeaders) {
                    fputcsv($csv, $params->getConfig()->get('fields'));
                }

                foreach ($params->getChunkedIds()->getChunks() as $ids) {
                    $rows = $this->getOrderDataAsArray($params->getConfig(), $ids);
                    foreach ($rows as $row) {
                        fputcsv($csv, $row);
                    }
                }
                fclose($csv);
                break;
            case 'xls':
                $spreadsheet = $this->prepareDataForXls($params);
                $writter = new Xls($spreadsheet);
                $writter->save($filePath);

                break;
            case 'xlsx':
                $spreadsheet = $this->prepareDataForXls($params);
                $writter = new Xlsx($spreadsheet);
                $writter->save($filePath);

                break;
        }
    }

    private function prepareDataForXls($params)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $useHeaders = $params->getConfig()->get(
            'headers',
            $this->getScheme()->getField('headers')->getDefaultValue()
        );

        $record = 1;
        $col = 'A';
        if ($useHeaders) {
            foreach ($params->getConfig()->get('fields') as $item) {
                $sheet->setCellValue($col . $record, $item);
                $col++;
            }
            $record++;
            $col = 'A';
        }

        foreach ($params->getChunkedIds()->getChunks() as $ids) {
            $rows = $this->getOrderDataAsArray($params->getConfig(), $ids);
            foreach ($rows as $row) {
                foreach ($row as $item) {
                    $sheet->setCellValue($col . $record, $item);
                    $col++;
                }
                $record++;
                $col = 'A';
            }
            #
        }

        return $spreadsheet;
    }

    private function getOrderDataAsArray(StoredConfig $config, array $ids): array
    {
        $pageSize = count($ids);
        $ids = implode(',', $ids);

        $fields = [];
        foreach ($config->get('fields') as $field) {
            $items = array_reverse(explode('.', $field));
            $tree = [];
            foreach ($items as $item) {
                if (empty($tree)) {
                    $tree[] = $item;
                } else {
                    $tree = [$item => $tree];
                }
            }
            $fields = array_merge_recursive($fields, $tree);
        }

        $fields = json_encode($fields);
        $fields = preg_replace('~"\d+":~', '', $fields);
        $fields = str_replace(['"', ':'], '', $fields);
        $fields = str_replace(['[', ']'], ['{', '}'], $fields);

        $query = <<<QUERY
query {
  company(token: "{$this->apiParams->getToken()}") {
     ordersFetcher(pagination: {pageNumber: 1, pageSize: {$pageSize}}, filters: {ids: [{$ids}]}) {
       orders {$fields}
     }
  }
}
QUERY;

        $response = $this->getGraphQLClient()->query($query)->getData();
        $orders = [];
        foreach ($response['company']['ordersFetcher']['orders'] as $order) {
            $dot = new Dot($order);
            $orders[] = $dot->flatten('.');
        }

        return $orders;
    }

    private function getFields(): array
    {
        $query = <<<QUERY
query {
  company(token:"{$this->apiParams->getToken()}") {
    fieldsFetcher {
      fields {
        name
        definition {
          __typename
        }
      }
    }
  }
}
QUERY;

        $response = $this->getGraphQLClient()->query($query)->getData();
        $fields = [];
        foreach ($response['company']['fieldsFetcher']['fields'] as $fieldData) {
            $name = $fieldData['name'];
            switch ($fieldData['definition']['__typename']) {
                case 'CheckboxFieldDefinition':
                case 'DatetimeFieldDefinition':
                case 'DropdownFieldDefinition':
                case 'EmailFieldDefinition':
                case 'FileFieldDefinition':
                case 'FloatFieldDefinition':
                case 'ImageFieldDefinition':
                case 'IntFieldDefinition':
                case 'PhoneFieldDefinition':
                case 'StringFieldDefinition':
                    $fields[] = "orderData.{$name}";
                    break;
                case 'AddressFieldDefinition':
                    $fields[] = "orderData.{$name}.postcode";
                    $fields[] = "orderData.{$name}.region";
                    $fields[] = "orderData.{$name}.city";
                    $fields[] = "orderData.{$name}.address_1";
                    $fields[] = "orderData.{$name}.address_2";
                    break;
                case 'HumanNameFieldDefinition':
                    $fields[] = "orderData.{$name}.firstName";
                    $fields[] = "orderData.{$name}.lastName";
                    break;
                case 'UserFieldDefinition':
                    $fields[] = "orderData.{$name}.id";
                    $fields[] = "orderData.{$name}.name.firstName";
                    $fields[] = "orderData.{$name}.name.lastName";
                    $fields[] = "orderData.{$name}.email";
                    break;
            }
        }

        return array_merge([
            'id',
            'project.id',
            'project.name',
            'status.id',
            'status.name',
            'status.group',
            'statusChangedAt',
            'createdAt',
            'updatedAt',
            'canceledAt',
            'approvedAt',
            'shippedAt',
            'deliveredAt',
            'undeliveredAt',
            'refundedAt',
            'warehouse.id',
            'warehouse.name',
            "initCartPrice",
            "cart.totalPrice",
            'source.url',
            'source.refererUrl',
            'source.ip',
            'source.utm_source',
            'source.utm_medium',
            'source.utm_campaign',
            'source.utm_content',
            'source.utm_term',
            'source.subid_1',
            'source.subid_2',
        ], $fields);
    }

    private function getGraphQLClient(): Client
    {
        return ClientBuilder::build($this->apiParams->getEndpointUrl());
    }

//    /**
//     * Should be called after every chunk handled (not every id, chunk only)
//     * @param array $ids
//     * @return mixed
//     */
//    public function sendProgress(WebhookManager $manager, array $ids)
//    {
//        // TODO: Implement sendProgress() method.
//    }
//
//    /**
//     * @param BatchResultInterface $batchResult
//     * @return mixed
//     */
//    public function sendResult(WebhookManager $manager, BatchResultInterface $batchResult)
//    {
//        $manager = new WebhookManager($this);
//    }

    /**
     * Should be called after every chunk handled (not every id, chunk only)
     * @param array $ids
     * @return mixed
     */
    public function sendProgress(array $ids)
    {
        // TODO: Implement sendProgress() method.
    }

    /**
     * @param BatchResultInterface $batchResult
     * @return mixed
     */
    public function sendResult(BatchResultInterface $batchResult)
    {
        // TODO: Implement sendResult() method.
    }
}