<?php
/**
 * Created for plugin-exporter-excel
 * Datetime: 31.07.2019 12:15
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Exporter\Handler\Excel;


use Exception;
use Leadvertex\Plugin\Components\ApiClient\ApiClient;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ArrayDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\BooleanDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\EnumDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Exporter\Handler\Components\Lang;

class ExcelForm extends Form
{

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * ExcelForm constructor.
     * @param ApiClient $apiClient
     * @throws Exception
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
        parent::__construct(Excel::getName(), Excel::getDescription(), [
            'main' => new FieldGroup(
                new Lang(
                    "Main settings",
                    "Основные настройки"
                ),
                [
                    'fields' => new ArrayDefinition(
                        new Lang(
                            "Fields to export",
                            "Поля для выгрузки"
                        ),
                        new Lang(
                            "Fields with this order will be exported to excel table",
                            "Поля будут выгружены в таблицу excel в заданной последовательности"
                        ),
                        $this->getApiFields(),
                        [
                            'id',
                            'project.id',
                            'project.name',
                            'status.id',
                            'status.name',
                        ],
                        true
                    ),
                    'format' => new EnumDefinition(
                        new Lang(
                            "File format",
                            "Формат файла"
                        ),
                        new Lang(
                            "csv - simple plain-text format, xls - old excel 2003 format, xlsx - new excel format",
                            "csv - простой текстовый формат, xls - формат excel 2003, xlsx - новый формат excel"
                        ),
                        [
                            'csv' => new Lang(
                                "*.csv - simple plain text format",
                                "*.csv - простой текстовый формат"
                            ),
                            'xls' => new Lang(
                                "*.xls - Excel 2003",
                                "*.xls - Формат Excel 2003"
                            ),
                            'xlsx' => new Lang(
                                "*.xls - Excel 2007 and newer",
                                "*.xls - Формат Excel 2007 и новее"
                            ),
                        ],
                        'csv',
                        true
                    ),
                    'headers' => new BooleanDefinition(
                        new Lang(
                            "Column names",
                            "Названия колонок"
                        ),
                        new Lang(
                            "Add column names at first wor",
                            "Добавлять названия колонок на первой строчке"
                        ),
                        true,
                        false
                    ),
                ]
            ),
        ]);
    }


    private function getApiFields(): array
    {
        $query = <<<QUERY
query {
  company {
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

        $response = $this->apiClient->query($query, [])->getData();
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
                    $fields["orderData.{$name}"] = new Lang(
                        $name,
                        $name
                    );
                    break;
                case 'AddressFieldDefinition':
                    $fields["orderData.{$name}.postcode"] = new Lang(
                        "{$label}: Address postcode",
                        "{$label}: Почтовый индекс"
                    );
                    $fields["orderData.{$name}.region"] = new Lang(
                        "{$label}: Region",
                        "{$label}: Регион"
                    );
                    $fields["orderData.{$name}.city"] = new Lang(
                        "{$label}: City",
                        "{$label}: Город"
                    );
                    $fields["orderData.{$name}.address_1"] = new Lang(
                        "{$label}: First address",
                        "{$label}: Первый адрес"
                    );
                    $fields["orderData.{$name}.address_2"] = new Lang(
                        "{$label}: Second address",
                        "{$label}: Второй адрес"
                    );
                    break;
                case 'HumanNameFieldDefinition':
                    $fields["orderData.{$name}.firstName"] = new Lang(
                        "{$label}: User first name",
                        "{$label}: Имя"
                    );
                    $fields["orderData.{$name}.lastName"] = new Lang(
                        "{$label}: User last name",
                        "{$label}: Фамилия"
                    );
                    break;
                case 'UserFieldDefinition':
                    $fields["orderData.{$name}.id"] = new Lang(
                        "{$label}: User ID",
                        "{$label}: ID пользователя"
                    );
                    $fields["orderData.{$name}.name.firstName"] = new Lang(
                        "{$label}: User first name",
                        "{$label}: Имя пользователя"
                    );
                    $fields["orderData.{$name}.name.lastName"] = new Lang(
                        "{$label}: User last name",
                        "{$label}: Фамилия пользователя"
                    );
                    $fields["orderData.{$name}.email"] = new Lang(
                        "{$label}: User email",
                        "{$label}: Электронаня почта пользователя"
                    );
                    break;
            }
        }

        return array_merge([
            'id' => new Lang(
                "ID",
                "ID"
            ),
            'project.id' => new Lang(
                "Project ID",
                "ID проекта"
            ),
            'project.name' => new Lang(
                "Project name",
                "Название проекта"
            ),
            'status.id' => new Lang(
                "Status ID",
                "ID статуса"
            ),
            'status.name' => new Lang(
                "Status name",
                "Название статуса"
            ),
            'status.group' => new Lang(
                "Status group",
                "Группа статуса"
            ),
            'statusChangedAt' => new Lang(
                "Status changed At",
                "Дата изменения статуса"
            ),
            'createdAt' => new Lang(
                "Created At",
                "Дата создания"
            ),
            'updatedAt' => new Lang(
                "Updated At",
                "Дата обновления"
            ),
            'canceledAt' => new Lang(
                "Canceled At",
                "Дата отмены"
            ),
            'approvedAt' => new Lang(
                "Approved At",
                "Дата подтверждения"
            ),
            'shippedAt' => new Lang(
                "Shipped At",
                "Дата отправки"
            ),
            'deliveredAt' => new Lang(
                "Delivered At",
                "Дата доставки"
            ),
            'undeliveredAt' => new Lang(
                "Undelivered At",
                "Дата недоставки"
            ),
            'refundedAt' => new Lang(
                "Refunded At",
                "Дата возврата"
            ),
            'warehouse.id' => new Lang(
                "Warehouse id",
                "ID склада"
            ),
            'warehouse.name' => new Lang(
                "Warehouse name",
                "Название склада"
            ),
            "initCartPrice" => new Lang(
                "Order price: initial",
                "Цена заказа: начальная"
            ),
            "cart.totalPrice" => new Lang(
                "Order price: current",
                "Цена заказа: текущая"
            ),
            'source.url' => new Lang(
                "Source: URL",
                "Источник: URL"
            ),
            'source.refererUrl' => new Lang(
                "Source: Referer",
                "Источник: Referer"
            ),
            'source.ip' => new Lang(
                "Source: IP",
                "Источник: IP"
            ),
            'source.utm_source' => new Lang(
                "Source: UTM-source",
                "Источник: UTM-source"
            ),
            'source.utm_medium' => new Lang(
                "Source: UTM-medium",
                "Источник: UTM-medium"
            ),
            'source.utm_campaign' => new Lang(
                "Source: UTM-campaign",
                "Источник: UTM-campaign"
            ),
            'source.utm_content' => new Lang(
                "Source: UTM-content",
                "Источник: UTM-content"
            ),
            'source.utm_term' => new Lang(
                "Source: UTM-term",
                "Источник: UTM-term"
            ),
            'source.subid_1' => new Lang(
                "Source: subid_1",
                "Источник: subid_1"
            ),
            'source.subid_2' => new Lang(
                "Source: subid_2",
                "Источник: subid_2"
            ),
        ], $fields);
    }

}