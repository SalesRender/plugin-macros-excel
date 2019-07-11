<?php
/**
 * Created for lv-exports.
 * Datetime: 03.07.2018 12:53
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Export\Format\Excel;


use Adbar\Dot;
use Leadvertex\Plugin\Export\Core\Components\ApiParams;
use Leadvertex\Plugin\Export\Core\Components\BatchResult\BatchResultInterface;
use Leadvertex\Plugin\Export\Core\Components\BatchResult\BatchResultSuccess;
use Leadvertex\Plugin\Export\Core\Components\GenerateParams;
use Leadvertex\Plugin\Export\Core\Components\StoredConfig;
use Leadvertex\Plugin\Export\Core\Components\WebhookManager;
use Leadvertex\Plugin\Export\Core\Formatter\FormatterInterface;
use Leadvertex\Plugin\Export\Core\Formatter\Type;
use Leadvertex\Plugin\Scheme\Components\i18n;
use Leadvertex\Plugin\Scheme\Components\Lang;
use Leadvertex\Plugin\Scheme\Developer;
use Leadvertex\Plugin\Scheme\FieldDefinitions\ArrayDefinition;
use Leadvertex\Plugin\Scheme\FieldDefinitions\BooleanDefinition;
use Leadvertex\Plugin\Scheme\FieldDefinitions\EnumDefinition;
use Leadvertex\Plugin\Scheme\FieldGroup;
use Leadvertex\Plugin\Scheme\Scheme;
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
     * Should return human-friendly name of this exporter
     * @return i18n
     */
    public static function getName(): i18n
    {
        return i18n::instance([
            new Lang('en', 'Excel'),
            new Lang('ru', 'Excel'),
        ]);
    }

    /**
     * Should return human-friendly description of this exporter
     * @return i18n
     */
    public static function getDescription(): i18n
    {
        return i18n::instance([
            new Lang('en', 'Export orders to excel file'),
            new Lang('ru', 'Выгружает заказы в excel файл'),
        ]);
    }

    /**
     * @return Type of entities, that can be exported by plugin
     */
    public function getType(): Type
    {
        return new Type(Type::ORDERS);
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
                self::getName(),
                self::getDescription(),
                [
                    'main' => new FieldGroup(
                        i18n::instance([
                            new Lang('en', 'Main settings'),
                            new Lang('ru', 'Основные настройки'),
                        ]),
                        [
                            'fields' => new ArrayDefinition(
                                i18n::instance([
                                    new Lang('en', 'Fields to export'),
                                    new Lang('ru', 'Поля для выгрузки'),
                                ]),
                                i18n::instance([
                                    new Lang('en', 'Fields with this order will be exported to excel table'),
                                    new Lang('ru', 'Поля будут выгружены в таблицу excel в заданной последовательности'),
                                ]),
                                $fields,
                                ['id', 'project.id', 'project.name', 'status.id', 'status.name'],
                                true
                            ),
                            'format' => new EnumDefinition(
                                i18n::instance([
                                    new Lang('en', 'File format'),
                                    new Lang('ru', 'Формат файла'),
                                ]),
                                i18n::instance([
                                    new Lang('en', 'csv - simple plain-text format, xls - old excel 2003 format, xlsx - new excel format'),
                                    new Lang('ru', 'csv - простой текстовый формат, xls - формат excel 2003, xlsx - новый формат excel'),
                                ]),
                                [
                                    'csv' => i18n::instance([
                                        new Lang('en', '*.csv - simple plain text format'),
                                        new Lang('ru', '*.csv - простой текстовый формат'),
                                    ]),
                                    'xls' => i18n::instance([
                                        new Lang('en', '*.xls - Excel 2003'),
                                        new Lang('ru', '*.xls - Формат Excel 2003'),
                                    ]),
                                    'xlsx' => i18n::instance([
                                        new Lang('en', '*.xls - Excel 2007 and newer'),
                                        new Lang('ru', '*.xls - Формат Excel 2007 и новее'),
                                    ])
                                ],
                                'csv',
                                true
                            ),
                            'headers' => new BooleanDefinition(
                                i18n::instance([
                                    new Lang('en', 'Column names'),
                                    new Lang('ru', 'Названия колонок'),
                                ]),
                                i18n::instance([
                                    new Lang('en', 'Add column names at first wor'),
                                    new Lang('ru', 'Добавлять названия колонок на первой строчке'),
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
        if (!$config->has('main')) {
            return false;
        }

        $schemeFields = array_keys($this->getFields());
        $storedFields = $config->get('main.fields', []);

        if (empty($storedFields)) {
            return false;
        }

        if (!empty(array_diff($storedFields, $schemeFields))) {
            return false;
        }

        $useHeaders = $config->get(
            'main.headers',
            $this->getScheme()->getGroup('main')->getField('headers')->getDefaultValue()
        );

        if (!is_bool($useHeaders)) {
            return false;
        }

        $defaultFormat = $this->getScheme()->getGroup('main')->getField('format')->getDefaultValue();
        $format = $config->get('main.format', $defaultFormat);
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
        $format = $params->getConfig()->get('main.format', $defaultFormat);
        $prefix = $params->getBatchParams()->getToken();
        $filePath = Path::canonicalize("{$this->publicDir}/{$prefix}.{$format}");
        $webHookManager = new WebhookManager($params->getBatchParams());

        switch ($format) {
            case 'csv':
                $csv = fopen($filePath, 'w');

                $useHeaders = $params->getConfig()->get(
                    'main.headers',
                    $this->getScheme()->getGroup('main')->getField('headers')->getDefaultValue()
                );

                if ($useHeaders) {
                    fputcsv($csv, $params->getConfig()->get('main.fields'));
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
            'main.headers',
            $this->getScheme()->getGroup('main')->getField('headers')->getDefaultValue()
        );

        $record = 1;
        $col = 'A';
        if ($useHeaders) {
            foreach ($params->getConfig()->get('main.fields') as $item) {
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
        foreach ($config->get('main.fields') as $field) {
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
          ... on BooleanFieldDefinition {
            label
          }
          ... on DatetimeFieldDefinition {
            label
          }
          ... on EnumFieldDefinition {
            label
          }
          ... on EmailFieldDefinition {
            label
          }
          ... on FileFieldDefinition {
            label
          }
          ... on FloatFieldDefinition {
            label
          }
          ... on ImageFieldDefinition {
            label
          }
          ... on IntFieldDefinition {
            label
          }
          ... on PhoneFieldDefinition {
            label
          }
          ... on StringFieldDefinition {
            label
          }
          ... on AddressFieldDefinition {
            label
          }
          ... on HumanNameFieldDefinition {
            label
          }
          ... on UserFieldDefinition {
            label
          }
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
                case 'BooleanFieldDefinition':
                case 'DatetimeFieldDefinition':
                case 'EnumFieldDefinition':
                case 'EmailFieldDefinition':
                case 'FileFieldDefinition':
                case 'FloatFieldDefinition':
                case 'ImageFieldDefinition':
                case 'IntFieldDefinition':
                case 'PhoneFieldDefinition':
                case 'StringFieldDefinition':
                    $fields["orderData.{$name}"] = i18n::instance([
                        new Lang('en', $name),
                        new Lang('ru', $name),
                    ]);
                    break;
                case 'AddressFieldDefinition':
                    $fields["orderData.{$name}.postcode"] = i18n::instance([
                        new Lang('en', "{$label}: Address postcode"),
                        new Lang('ru', "{$label}: Почтовый индекс"),
                    ]);
                    $fields["orderData.{$name}.region"] = i18n::instance([
                        new Lang('en', "{$label}: Region"),
                        new Lang('ru', "{$label}: Регион"),
                    ]);
                    $fields["orderData.{$name}.city"] = i18n::instance([
                        new Lang('en', "{$label}: City"),
                        new Lang('ru', "{$label}: Город"),
                    ]);
                    $fields["orderData.{$name}.address_1"] = i18n::instance([
                        new Lang('en', "{$label}: First address"),
                        new Lang('ru', "{$label}: Первый адрес"),
                    ]);
                    $fields["orderData.{$name}.address_2"] = i18n::instance([
                        new Lang('en', "{$label}: Second address"),
                        new Lang('ru', "{$label}: Второй адрес"),
                    ]);
                    break;
                case 'HumanNameFieldDefinition':
                    $fields["orderData.{$name}.firstName"] = i18n::instance([
                        new Lang('en', "{$label}: User first name"),
                        new Lang('ru', "{$label}: Имя"),
                    ]);
                    $fields["orderData.{$name}.lastName"] = i18n::instance([
                        new Lang('en', "{$label}: User last name"),
                        new Lang('ru', "{$label}: Фамилия"),
                    ]);
                    break;
                case 'UserFieldDefinition':
                    $fields["orderData.{$name}.id"] = i18n::instance([
                        new Lang('en', "{$label}: User ID"),
                        new Lang('ru', "{$label}: ID пользователя"),
                    ]);
                    $fields["orderData.{$name}.name.firstName"] = i18n::instance([
                        new Lang('en', "{$label}: User first name"),
                        new Lang('ru', "{$label}: Имя пользователя"),
                    ]);
                    $fields["orderData.{$name}.name.lastName"] = i18n::instance([
                        new Lang('en', "{$label}: User last name"),
                        new Lang('ru', "{$label}: Фамилия пользователя"),
                    ]);
                    $fields["orderData.{$name}.email"] = i18n::instance([
                        new Lang('en', "{$label}: User email"),
                        new Lang('ru', "{$label}: Электронаня почта пользователя"),
                    ]);
                    break;
            }
        }

        return array_merge([
            'id' => i18n::instance([
                new Lang('en', 'ID'),
                new Lang('ru', 'ID'),
            ]),
            'project.id' => i18n::instance([
                new Lang('en', 'Project ID'),
                new Lang('ru', 'ID проекта'),
            ]),
            'project.name' => i18n::instance([
                new Lang('en', 'Project name'),
                new Lang('ru', 'Название проекта'),
            ]),
            'status.id' => i18n::instance([
                new Lang('en', 'Status ID'),
                new Lang('ru', 'ID статуса'),
            ]),
            'status.name' => i18n::instance([
                new Lang('en', 'Status name'),
                new Lang('ru', 'Название статуса'),
            ]),
            'status.group' => i18n::instance([
                new Lang('en', 'Status group'),
                new Lang('ru', 'Группа статуса'),
            ]),
            'statusChangedAt' => i18n::instance([
                new Lang('en', 'Status changed At'),
                new Lang('ru', 'Дата изменения статуса'),
            ]),
            'createdAt' => i18n::instance([
                new Lang('en', 'Created At'),
                new Lang('ru', 'Дата создания'),
            ]),
            'updatedAt' => i18n::instance([
                new Lang('en', 'Updated At'),
                new Lang('ru', 'Дата обновления'),
            ]),
            'canceledAt' => i18n::instance([
                new Lang('en', 'Canceled At'),
                new Lang('ru', 'Дата отмены'),
            ]),
            'approvedAt' => i18n::instance([
                new Lang('en', 'Approved At'),
                new Lang('ru', 'Дата подтверждения'),
            ]),
            'shippedAt' => i18n::instance([
                new Lang('en', 'Shipped At'),
                new Lang('ru', 'Дата отправки'),
            ]),
            'deliveredAt' => i18n::instance([
                new Lang('en', 'Delivered At'),
                new Lang('ru', 'Дата доставки'),
            ]),
            'undeliveredAt' => i18n::instance([
                new Lang('en', 'Undelivered At'),
                new Lang('ru', 'Дата недоставки'),
            ]),
            'refundedAt' => i18n::instance([
                new Lang('en', 'Refunded At'),
                new Lang('ru', 'Дата возврата'),
            ]),
            'warehouse.id' => i18n::instance([
                new Lang('en', 'Warehouse id'),
                new Lang('ru', 'ID склада'),
            ]),
            'warehouse.name' => i18n::instance([
                new Lang('en', 'Warehouse name'),
                new Lang('ru', 'Название склада'),
            ]),
            "initCartPrice" => i18n::instance([
                new Lang('en', 'Order price: initial'),
                new Lang('ru', 'Цена заказа: начальная'),
            ]),
            "cart.totalPrice" => i18n::instance([
                new Lang('en', 'Order price: current'),
                new Lang('ru', 'Цена заказа: текущая'),
            ]),
            'source.url' => i18n::instance([
                new Lang('en', 'Source: URL'),
                new Lang('ru', 'Источник: URL'),
            ]),
            'source.refererUrl' => i18n::instance([
                new Lang('en', 'Source: Referer URL'),
                new Lang('ru', 'Источник: Referer URL'),
            ]),
            'source.ip' => i18n::instance([
                new Lang('en', 'Source: IP'),
                new Lang('ru', 'Источник: IP'),
            ]),
            'source.utm_source' => i18n::instance([
                new Lang('en', 'Source: UTM-source'),
                new Lang('ru', 'Источник: UTM-source'),
            ]),
            'source.utm_medium' => i18n::instance([
                new Lang('en', 'Source: UTM-medium'),
                new Lang('ru', 'Источник: UTM-medium'),
            ]),
            'source.utm_campaign' => i18n::instance([
                new Lang('en', 'Source: UTM-campaign'),
                new Lang('ru', 'Источник: UTM-campaign'),
            ]),
            'source.utm_content' => i18n::instance([
                new Lang('en', 'Source: UTM-content'),
                new Lang('ru', 'Источник: UTM-content'),
            ]),
            'source.utm_term' => i18n::instance([
                new Lang('en', 'Source: UTM-term'),
                new Lang('ru', 'Источник: UTM-term'),
            ]),
            'source.subid_1' => i18n::instance([
                new Lang('en', 'Source: subid_1'),
                new Lang('ru', 'Источник: subid_1'),
            ]),
            'source.subid_2' => i18n::instance([
                new Lang('en', 'Source: subid_2'),
                new Lang('ru', 'Источник: subid_2'),
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