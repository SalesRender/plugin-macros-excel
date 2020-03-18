<?php
/**
 * Created for plugin-exporter-excel
 * Date: 04.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Macros\Components;


use Leadvertex\Plugin\Components\ApiClient\ApiClient;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Core\Macros\Models\Session;

class Columns
{

    /** @var ApiClient */
    private $client;

    public function __construct()
    {
        $this->client = Session::current()->getApiClient();
    }

    public function getList(): array
    {
        return array_merge(
            $this->getSystemColumns(),
            $this->getCartColumns(),
            $this->getCustomColumns()
        );
    }

    public static function getQueryColumns(array $inputFields): array
    {
        $handledFields = [];
        foreach ($inputFields as $inputField) {
            if (FieldParser::hasFilter($inputField)) {
                $field = new FieldParser($inputField);
                $handledFields[] = $field->getPropertyPart();
                $handledFields[] = $field->getValuePart();
            } else {
                $handledFields[] = $inputField;
            }
        }

        $fields = [];
        foreach ($handledFields as $field) {
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

        $fields[] = 'id';
        return ['orders' => $fields];
    }

    private function getSystemColumns(): array
    {
        $fields = [
            'id' => 'ID',

            'project.id' => Translator::get('fields', 'Проект (ID)'),
            'project.name' => Translator::get('fields', 'Проект (название)'),

            'status.id' => Translator::get('fields', 'Статус (ID)'),
            'status.name' => Translator::get('fields', 'Статус (название)'),
            'status.group' => Translator::get('fields', 'Статус (группа)'),

            'createdAt' => Translator::get('fields', 'Дата создания'),
            'updatedAt' => Translator::get('fields', 'Дата изменения'),
            'canceledAt' => Translator::get('fields', 'Дата отмены'),
            'approvedAt' => Translator::get('fields', 'Дата подтверждения'),
            'shippedAt' => Translator::get('fields', 'Дата отправки'),
            'deliveredAt' => Translator::get('fields', 'Дата доставки'),
            'undeliveredAt' => Translator::get('fields', 'Дата неудачной доставки'),
            'refundedAt' => Translator::get('fields', 'Дата возврата'),

            'warehouse.id' => Translator::get('fields', 'Склад (ID)'),
            'warehouse.name' => Translator::get('fields', 'Склад (название)'),

            'vat.price' => Translator::get('fields', 'Налог (сумма)'),
            'vat.percent' => Translator::get('fields', 'Налог (процент)'),
            'vat.includeShipping' => Translator::get('fields', 'Налог (включает доставку)'),

            'lead.webmaster.id' => Translator::get('fields', 'Lead (ID вебмастера)'),
            'lead.webmaster.email' => Translator::get('fields', 'Lead (email вебмастера)'),
            'lead.offer.id' => Translator::get('fields', 'Lead (ID оффера)'),
            'lead.rewardMethod' => Translator::get('fields', 'Lead (метод вознаграждения)'),
            'lead.bid.type' => Translator::get('fields', 'Lead (тип ставки)'),
            'lead.bid.value' => Translator::get('fields', 'Lead (ставка)'),
            'lead.bid.currency' => Translator::get('fields', 'Lead (валюта)'),
            'lead.reward.amount' => Translator::get('fields', 'Lead (сумма вознаграждение)'),
            'lead.reward.currency' => Translator::get('fields', 'Lead (валюта вознаграждение)'),
            'lead.status' => Translator::get('fields', 'Lead (статус)'),
            'lead.holdTo' => Translator::get('fields', 'Lead (холд до)'),
            'lead.finished' => Translator::get('fields', 'Lead (завершен)'),

            'source.uri' => Translator::get('fields', 'Источник (uri)'),
            'source.refererUri' => Translator::get('fields', 'Источник (referer)'),
            'source.ip' => Translator::get('fields', 'Источник (IP)'),
            'source.utm_source' => Translator::get('fields', 'Источник (utm_source)'),
            'source.utm_medium' => Translator::get('fields', 'Источник (utm_medium)'),
            'source.utm_campaign' => Translator::get('fields', 'Источник (utm_campaign)'),
            'source.utm_content' => Translator::get('fields', 'Источник (utm_content)'),
            'source.utm_term' => Translator::get('fields', 'Источник (utm_term)'),
            'source.subid_1' => Translator::get('fields', 'Источник (subid_1)'),
            'source.subid_2' => Translator::get('fields', 'Источник (subid_2)'),
        ];

        $result = [];
        foreach ($fields as $field => $title) {
            $result[$field] = [
                'title' => $title,
                'group' => Translator::get('fields', 'Системные')
            ];
        }
        return $result;
    }

    private function getCartColumns(): array
    {
        $fields = [
            'cart.totalPrice' => Translator::get('fields', 'Корзина (сумма)'),
            'cart.singleItems' => Translator::get('fields', 'Корзина (товары вне акций)'),
            'cart.promotionalItems' => Translator::get('fields', 'Корзина (товары по акциям)'),
        ];

        $result = [];
        foreach ($fields as $field => $title) {
            $result[$field] = [
                'title' => $title,
                'group' => Translator::get('fields', 'Корзина')
            ];
        }
        return $result;
    }

    private function getCustomColumns(): array
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

        $response = $this->client->query($query, [])->getData();

        $groups = [];
        foreach ($response['company']['fieldsFetcher']['fields'] as $fieldData) {
            $name = $fieldData['name'];
            $label = $fieldData['definition']['label'];
            $typename = str_replace('Definition', 's', $fieldData['definition']['__typename']);
            switch ($fieldData['definition']['__typename']) {
                case 'BooleanFieldDefinition':
                    $groups[Translator::get('fields', 'Логический (да/нет)')] = [
                        "orderData.{$typename}.[field={$name}].value" => $label,
                    ];
                    break;
                case 'DatetimeFieldDefinition':
                    $groups[Translator::get('fields', 'Дата и время')] = [
                        "orderData.{$typename}.[field={$name}].value" => $label,
                    ];
                    break;
                case 'EnumFieldDefinition':
                    $groups[Translator::get('fields', 'Списки')] = [
                        "orderData.{$typename}.[field={$name}].value" => $label,
                    ];
                    break;
                case 'EmailFieldDefinition':
                    $groups[Translator::get('fields', 'Email')] = [
                        "orderData.{$typename}.[field={$name}].value.raw" => $label,
                        "orderData.{$typename}.[field={$name}].value.duplicates" => Translator::get(
                            'fields',
                            '{label} (дублей)',
                            ['label' => $label]
                        ),
                    ];
                    break;
                case 'FileFieldDefinition':
                    $groups[Translator::get('fields', 'Файлы')] = [
                        "orderData.{$typename}.[field={$name}].value" => $label, //todo will be changed
                    ];
                    break;
                case 'FloatFieldDefinition':
                    $groups[Translator::get('fields', 'Дробные числа')] = [
                        "orderData.{$typename}.[field={$name}].value" => $label,
                    ];
                    break;
                case 'ImageFieldDefinition':
                    $groups[Translator::get('fields', 'Изображения')] = [
                        "orderData.{$typename}.[field={$name}].value" => $label, //todo will be changed
                    ];
                    break;
                case 'IntFieldDefinition':
                    $groups[Translator::get('fields', 'Целые числа')] = [
                        "orderData.{$typename}.[field={$name}].value" => $label,
                    ];
                    break;
                case 'PhoneFieldDefinition':
                    $groups[Translator::get('fields', 'Телефоны')] = [
                        "orderData.{$typename}.[field={$name}].value.raw" => Translator::get(
                            'fields',
                            '{label} (исходный)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.international" => Translator::get(
                            'fields',
                            '{label} (международный)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.national" => Translator::get(
                            'fields',
                            '{label} (локальный)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.country" => Translator::get(
                            'fields',
                            '{label} (код страны)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.duplicates" => Translator::get(
                            'fields',
                            '{label} (дублей)',
                            ['label' => $label]
                        ),
                    ];
                    break;
                case 'StringFieldDefinition':
                    $groups[Translator::get('fields', 'Строки')] = [
                        "orderData.{$typename}.[field={$name}].value" => $label,
                    ];
                    break;
                case 'AddressFieldDefinition':
                    $groups[Translator::get('fields', 'Адреса')] = [
                        "orderData.{$typename}.[field={$name}].value.postcode" => Translator::get(
                            'fields',
                            '{label} (почтовый индекс)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.region" => Translator::get(
                            'fields',
                            '{label} (регион)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.city" => Translator::get(
                            'fields',
                            '{label} (город)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.address_1" => Translator::get(
                            'fields',
                            '{label} (адрес 1)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.address_2" => Translator::get(
                            'fields',
                            '{label} (адрес 2)',
                            ['label' => $label]
                        ),
                    ];
                    break;
                case 'HumanNameFieldDefinition':
                    $groups[Translator::get('fields', 'Ф.И.О')] = [
                        "orderData.{$typename}.[field={$name}].value.firstName" => Translator::get(
                            'fields',
                            '{label} (имя)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.lastName" => Translator::get(
                            'fields',
                            '{label} (фамилия)',
                            ['label' => $label]
                        ),
                    ];
                    break;
                case 'UserFieldDefinition':
                    $groups[Translator::get('fields', 'Пользователи')] = [
                        "orderData.{$typename}.[field={$name}].value.id" => Translator::get(
                            'fields',
                            '{label} (ID)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.email" => Translator::get(
                            'fields',
                            '{label} (email)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.name.firstName" => Translator::get(
                            'fields',
                            '{label} (имя)',
                            ['label' => $label]
                        ),
                        "orderData.{$typename}.[field={$name}].value.name.lastName" => Translator::get(
                            'fields',
                            '{label} (фамилия)',
                            ['label' => $label]
                        ),
                    ];
                    break;
            }
        }

        $result = [];
        foreach ($groups as $group => $fields) {
            foreach ($fields as $field => $title) {
                $result[$field] = [
                    'title' => $title,
                    'group' => $group
                ];
            }
        }
        return $result;
    }

}