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
use Leadvertex\External\Export\Core\Components\BatchResult\BatchResultSuccess;
use Leadvertex\External\Export\Core\Components\Developer;
use Leadvertex\External\Export\Core\Components\GenerateParams;
use Leadvertex\External\Export\Core\Components\MultiLang;
use Leadvertex\External\Export\Core\Components\StoredConfig;
use Leadvertex\External\Export\Core\Components\WebhookManager;
use Leadvertex\External\Export\Core\FieldDefinitions\ArrayDefinition;
use Leadvertex\External\Export\Core\FieldDefinitions\CheckboxDefinition;
use Leadvertex\External\Export\Core\FieldDefinitions\EnumDefinition;
use Leadvertex\External\Export\Core\Formatter\FieldGroup;
use Leadvertex\External\Export\Core\Formatter\FormatterInterface;
use Leadvertex\External\Export\Core\Formatter\Scheme;
use Leadvertex\External\Export\Core\Formatter\Type;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Softonic\GraphQL\Client;
use Softonic\GraphQL\ClientBuilder;
use Webmozart\PathUtil\Path;

class Excel implements FormatterInterface
{

    /** @var Scheme */
    private $scheme;

    /** @var ApiParams */
    private $apiParams;

    /** @var string */
    private $runtimeDir;

    /** @var string */
    private $publicDir;

    /** @var string */
    private $publicUrl;

    public function __construct(ApiParams $apiParams, string $runtimeDir, string $publicDir, string $publicUrl)
    {
        $this->apiParams = $apiParams;
        $this->runtimeDir = $runtimeDir;
        $this->publicDir = $publicDir;
        $this->publicUrl = $publicUrl;
    }

    /**
     * @return Scheme
     * @throws \Exception
     */
    public function getScheme(): Scheme
    {
        if (!$this->scheme) {
            $fields = $this->getFields();
            $this->scheme = new Scheme(
                new Developer('LeadVertex', 'support@leadvertex.com', 'exports.leadvertex.com'),
                new Type(Type::ORDERS),
                new MultiLang([
                        'en' => 'Excel'
                ]),
                new MultiLang([
                    'en' => 'Export orders to excel file',
                    'ru' => 'Выгружает заказы в excel файл',
                ]),
                [
                    'main' => new FieldGroup(
                        new MultiLang([
                            'en' => 'Main settings',
                            'ru' => 'Основные настройки'
                        ]),
                        [
                            'fields' => new ArrayDefinition(
                                new MultiLang([
                                    'en' => 'Fields to export',
                                    'ru' => 'Поля для выгрузки',
                                ]),
                                new MultiLang([
                                    'en' => 'Fields with this order will be exported to excel table',
                                    'ru' => 'Поля будут выгружены в таблицу excel в заданной последовательности',
                                ]),
                                [
                                    'id' => new MultiLang([
                                        'en' => 'ID',
                                        'ru' => 'ID'
                                    ]),
                                    'project.id' => new MultiLang([
                                        'en' => 'Project ID',
                                        'ru' => 'ID проекта'
                                    ]),
                                    'project.name' => new MultiLang([
                                        'en' => 'Project name',
                                        'ru' => 'Имя проекта'
                                    ]),
                                    'status.id' => new MultiLang([
                                        'en' => 'Status ID',
                                        'ru' => 'ID статуса'
                                    ]),
                                    'status.name' => new MultiLang([
                                        'en' => 'Status name',
                                        'ru' => 'Имя статуса'
                                    ]),
                                ],
                                $fields,
                                true
                            ),
                            'format' => new EnumDefinition(
                                new MultiLang([
                                    'en' => 'File format',
                                    'ru' => 'Формат файла',
                                ]),
                                new MultiLang([
                                    'en' => 'csv - simple plain-text format, xls - old excel 2003 format, xlsx - new excel format',
                                    'ru' => 'csv - простой текстовый формат, xls - формат excel 2003, xlsx - новый формат excel',
                                ]),
                                [
                                    'csv' => new MultiLang([
                                        'en' => '*.csv - simple plain text format',
                                        'ru' => '*.csv - простой текстовый формат',
                                    ]),
                                    'xls' => new MultiLang([
                                        'en' => '*.xls - Excel 2003',
                                        'ru' => '*.xls - Формат Excel 2003',
                                    ]),
                                    'xlsx' => new MultiLang([
                                        'en' => '*.xls - Excel 2007 and newer',
                                        'ru' => '*.xls - Формат Excel 2007 и новее',
                                    ])
                                ],
                                'csv',
                                true
                            ),
                            'headers' => new CheckboxDefinition(
                                new MultiLang([
                                    'en' => 'Column names',
                                    'ru' => 'Названия колонок',
                                ]),
                                new MultiLang([
                                    'en' => 'Add column names at first wor',
                                    'ru' => 'Добавлять названия колонок на первой строчке',
                                ]),
                                true,
                                true
                            )
                        ]
                    ),
                ]
            );
        }

        return $this->scheme;
    }

    /**
     * @param StoredConfig $config
     * @return bool
     * @throws \Exception
     */
    public function isConfigValid(StoredConfig $config): bool
    {
        if (!$config->has('fields')) {
            return false;
        }

        $schemeFields = array_keys($this->getFields());
        $storedFields = $config->get('fields', []);

        if (!empty(array_diff($storedFields, $schemeFields))) {
            return false;
        }


        $useHeaders = $config->get(
            'headers',
            $this->getScheme()->getGroup('main')->getField('headers')->getDefaultValue()
        );

        if (!is_bool($useHeaders)) {
            return false;
        }

        $defaultFormat = $this->getScheme()->getGroup('main')->getField('format')->getDefaultValue();
        $format = $config->get('format', $defaultFormat);
        if (!in_array($format, ['csv', 'xls', 'xlsx'])) {
            return false;
        }

        return true;
    }

    /**
     * @param GenerateParams $params
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function generate(GenerateParams $params)
    {
        $defaultFormat = $this->getScheme()->getGroup('main')->getField('format')->getDefaultValue();
        $format = $params->getConfig()->get('format', $defaultFormat);
        $prefix = $params->getBatchParams()->getToken();
        $filePath = Path::canonicalize("{$this->publicDir}/{$prefix}.{$format}");
        $webHookManager = new WebhookManager($params->getBatchParams());

        switch ($format) {
            case 'csv':
                $csv = fopen($filePath, 'w');

                $useHeaders = $params->getConfig()->get(
                    'headers',
                    $this->getScheme()->getGroup('main')->getField('headers')->getDefaultValue()
//                    $this->getScheme()->getField('headers')->getDefaultValue()
                );

                if ($useHeaders) {
                    fputcsv($csv, $params->getConfig()->get('fields'));
                }

                foreach ($params->getChunkedIds()->getChunks() as $ids) {
                    $rows = $this->getOrderDataAsArray($params->getConfig(), $ids);
                    foreach ($rows as $row) {
                        fputcsv($csv, $row);
                    }
                    $this->sendProgress($webHookManager, $ids);
                }
                fclose($csv);
                break;
            case 'xls':
                $spreadsheet = $this->prepareDataForXls($params, $webHookManager);
                $writer = new Xls($spreadsheet);
                $writer->save($filePath);
                break;
            case 'xlsx':
                $spreadsheet = $this->prepareDataForXls($params, $webHookManager);
                $writer = new Xlsx($spreadsheet);
                $writer->save($filePath);
                break;
        }

        $batchResult = new BatchResultSuccess(LV_EXPORT_PUBLIC_URL);

        $this->sendResult($webHookManager, $batchResult);
    }

    /**
     * @param GenerateParams $params
     * @param WebhookManager $webHookManager
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    private function prepareDataForXls(GenerateParams $params, WebhookManager $webHookManager)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $useHeaders = $params->getConfig()->get(
            'headers',
            $this->getScheme()->getGroup('main')->getField('headers')->getDefaultValue()
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
            $this->sendProgress($webHookManager, $ids);
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
            $label = $fieldData['definition']['label'];
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
                    $fields["orderData.{$name}"] = new MultiLang([
                        'en' => $name,
                        'ru' => $name
                    ]);
                    break;
                case 'AddressFieldDefinition':
                    $fields["orderData.{$name}.postcode"] = new MultiLang([
                        'en' => "{$label}: Address postcode",
                        'ru' => "{$label}: Почтовый индекс"
                    ]);
                    $fields["orderData.{$name}.region"] = new MultiLang([
                        'en' => "{$label}: Region",
                        'ru' => "{$label}: Регион"
                    ]);
                    $fields["orderData.{$name}.city"] = new MultiLang([
                        'en' => "{$label}: City",
                        'ru' => "{$label}: Город"
                    ]);
                    $fields["orderData.{$name}.address_1"] = new MultiLang([
                        'en' => "{$label}: First address",
                        'ru' => "{$label}: Первый адрес"
                    ]);
                    $fields["orderData.{$name}.address_2"] = new MultiLang([
                        'en' => "{$label}: Second address",
                        'ru' => "{$label}: Второй адрес"
                    ]);
                    break;
                case 'HumanNameFieldDefinition':
                    $fields["orderData.{$name}.firstName"] = new MultiLang([
                        'en' => "{$label}: User first name",
                        'ru' => "{$label}: Имя"
                    ]);
                    $fields["orderData.{$name}.lastName"] = new MultiLang([
                        'en' => "{$label}: User last name",
                        'ru' => "{$label}: Фамилия"
                    ]);
                    break;
                case 'UserFieldDefinition':
                    $fields["orderData.{$name}.id"] = new MultiLang([
                        'en' => "{$label}: User ID",
                        'ru' => "{$label}: ID пользователя"
                    ]);
                    $fields["orderData.{$name}.name.firstName"] = new MultiLang([
                        'en' => "{$label}: User first name",
                        'ru' => "{$label}: Имя пользователя"
                    ]);
                    $fields["orderData.{$name}.name.lastName"] = new MultiLang([
                        'en' => "{$label}: User last name",
                        'ru' => "{$label}: Фамилия пользователя"
                    ]);
                    $fields["orderData.{$name}.email"] = new MultiLang([
                        'en' => "{$label}: User email",
                        'ru' => "{$label}: Электронаня почта пользователя"
                    ]);
                    break;
            }
        }

        return array_merge([
            'id' => new MultiLang([
                'en' => 'ID',
                'ru' => 'ID'
            ]),
            'project.id' => new MultiLang([
                'en' => 'Project ID',
                'ru' => 'ID проекта'
            ]),
            'project.name' => new MultiLang([
                'en' => 'Project name',
                'ru' => 'Название проекта'
            ]),
            'status.id' => new MultiLang([
                'en' => 'Status ID',
                'ru' => 'ID статуса'
            ]),
            'status.name' => new MultiLang([
                'en' => 'Status name',
                'ru' => 'Название статуса'
            ]),
            'status.group' => new MultiLang([
                'en' => 'Status group',
                'ru' => 'Группа статуса'
            ]),
            'statusChangedAt' => new MultiLang([
                'en' => 'Status changed At',
                'ru' => 'Дата изменения статуса'
            ]),
            'createdAt' => new MultiLang([
                'en' => 'Created At',
                'ru' => 'Дата создания'
            ]),
            'updatedAt' => new MultiLang([
                'en' => 'Updated At',
                'ru' => 'Дата обновления'
            ]),
            'canceledAt' => new MultiLang([
                'en' => 'Canceled At',
                'ru' => 'Дата отмены'
            ]),
            'approvedAt' => new MultiLang([
                'en' => 'Approved At',
                'ru' => 'Дата подтверждения'
            ]),
            'shippedAt' => new MultiLang([
                'en' => 'Shipped At',
                'ru' => 'Дата отправки'
            ]),
            'deliveredAt' => new MultiLang([
                'en' => 'Delivered At',
                'ru' => 'Дата доставки'
            ]),
            'undeliveredAt' => new MultiLang([
                'en' => 'Undelivered At',
                'ru' => 'Дата недоставки'
            ]),
            'refundedAt' => new MultiLang([
                'en' => 'Refunded At',
                'ru' => 'Дата возврата'
            ]),
            'warehouse.id' => new MultiLang([
                'en' => 'Warehouse id',
                'ru' => 'ID склада'
            ]),
            'warehouse.name' => new MultiLang([
                'en' => 'Warehouse name',
                'ru' => 'Название склада'
            ]),
            "initCartPrice" => new MultiLang([
                'en' => 'Order price: initial',
                'ru' => 'Цена заказа: начальная'
            ]),
            "cart.totalPrice" => new MultiLang([
                'en' => 'Order price: current',
                'ru' => 'Цена заказа: текущая'
            ]),
            'source.url' => new MultiLang([
                'en' => 'Source: URL',
                'ru' => 'Ресурс: URL'
            ]),
            'source.refererUrl' => new MultiLang([
                'en' => 'Source: referer URL',
                'ru' => 'Ресурс: URL референта'
            ]),
            'source.ip' => new MultiLang([
                'en' => 'Source: IP',
                'ru' => 'Ресурс: IP'
            ]),
            'source.utm_source' => new MultiLang([
                'en' => 'Source: UTM-source',
                'ru' => 'Ресурс: UTM-source'
            ]),
            'source.utm_medium' => new MultiLang([
                'en' => 'Source: UTM-medium',
                'ru' => 'Ресурс: UTM-medium'
            ]),
            'source.utm_campaign' => new MultiLang([
                'en' => 'Source: UTM-campaign',
                'ru' => 'Ресурс: UTM-campaign'
            ]),
            'source.utm_content' => new MultiLang([
                'en' => 'Source: UTM-content',
                'ru' => 'Ресурс: UTM-content'
            ]),
            'source.utm_term' => new MultiLang([
                'en' => 'Source: UTM-term',
                'ru' => 'Ресурс: UTM-term'
            ]),
            'source.subid_1' => new MultiLang([
                'en' => 'Source: first sub-id',
                'ru' => 'Ресурс: первый дополнительный id'
            ]),
            'source.subid_2' => new MultiLang([
                'en' => 'Source: second sub-id',
                'ru' => 'Ресурс: второй дополнительный id'
            ]),
        ], $fields);
    }

    private function getGraphQLClient(): Client
    {
        return ClientBuilder::build($this->apiParams->getEndpointUrl());
    }

    /**
     * Should be called after every chunk handled (not every id, chunk only)
     * @param WebhookManager $manager
     * @param array $ids
     */
    public function sendProgress(WebhookManager $manager, array $ids)
    {
        $manager->progress($ids);
    }

    /**
     * @param WebhookManager $manager
     * @param BatchResultInterface $batchResult
     */
    public function sendResult(WebhookManager $manager, BatchResultInterface $batchResult)
    {
        $manager->result($batchResult);
    }
}