<?php
/**
 * Created for plugin-exporter-excel
 * Date: 04.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Excel\Components;


use Leadvertex\Plugin\Components\Access\Token\GraphqlInputToken;
use Leadvertex\Plugin\Components\ApiClient\ApiClient;
use Leadvertex\Plugin\Components\Translations\Translator;

class Columns
{

    private ApiClient $client;

    public function __construct()
    {
        $token = GraphqlInputToken::getInstance();
        $this->client = new ApiClient(
            "{$token->getBackendUri()}companies/{$token->getPluginReference()->getCompanyId()}/CRM",
            (string) $token->getOutputToken()
        );
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
            'statusChangedAt' => Translator::get('fields', 'Дата смены статуса'),

            'warehouse.id' => Translator::get('fields', 'Склад (ID)'),
            'warehouse.name' => Translator::get('fields', 'Склад (название)'),

            'vat.price' => Translator::get('fields', 'Налог (сумма)'),
            'vat.percent' => Translator::get('fields', 'Налог (процент)'),
            'vat.includeLogistic' => Translator::get('fields', 'Налог (включает доставку)'),

            'lead.webmaster.id' => Translator::get('fields', 'Lead (ID вебмастера)'),
            'lead.webmaster.email' => Translator::get('fields', 'Lead (email вебмастера)'),
            'lead.offer.id' => Translator::get('fields', 'Lead (ID оффера)'),
            'lead.rewardMethod' => Translator::get('fields', 'Lead (метод вознаграждения)'),
            'lead.reward.amount' => Translator::get('fields', 'Lead (сумма вознаграждение)'),
            'lead.reward.currency' => Translator::get('fields', 'Lead (валюта вознаграждение)'),
            'lead.status' => Translator::get('fields', 'Lead (статус)'),
            'lead.holdTo' => Translator::get('fields', 'Lead (холд до)'),
            'lead.finished' => Translator::get('fields', 'Lead (завершен)'),

            'source.uri' => Translator::get('fields', 'Источник (uri)'),
            'source.refererUri' => Translator::get('fields', 'Источник (referer)'),
            'source.ip.ip.address' => Translator::get('fields', 'Источник (IP адрес)'),
            'source.ip.ip.country' => Translator::get('fields', 'Источник (IP страна)'),
            'source.ip.ip.city' => Translator::get('fields', 'Источник (IP город)'),
            'source.ip.ip.timezone' => Translator::get('fields', 'Источник (IP часовой пояс)'),
            'source.ip.ip.location.latitude' => Translator::get('fields', 'Источник (IP широта)'),
            'source.ip.ip.location.longitude' => Translator::get('fields', 'Источник (IP долгота)'),
            'source.ip.duplicates' => Translator::get('fields', 'Источник (IP дубликаты)'),
            'source.utm_source' => Translator::get('fields', 'Источник (utm_source)'),
            'source.utm_medium' => Translator::get('fields', 'Источник (utm_medium)'),
            'source.utm_campaign' => Translator::get('fields', 'Источник (utm_campaign)'),
            'source.utm_content' => Translator::get('fields', 'Источник (utm_content)'),
            'source.utm_term' => Translator::get('fields', 'Источник (utm_term)'),
            'source.subid_1' => Translator::get('fields', 'Источник (subid_1)'),
            'source.subid_2' => Translator::get('fields', 'Источник (subid_2)'),

            'logistic.waybill.track' => Translator::get('fields', 'Накладная (Трек-номер)'),
            'logistic.waybill.price' => Translator::get('fields', 'Накладная (Стоимость доставки)'),
            'logistic.waybill.deliveryTerms.minHours' => Translator::get('fields', 'Накладная (Время доставки (c))'),
            'logistic.waybill.deliveryTerms.maxHours' => Translator::get('fields', 'Накладная (Время доставки (по))'),
            'logistic.waybill.deliveryType' => Translator::get('fields', 'Накладная (Тип доставки)'),
            'logistic.waybill.cod' => Translator::get('fields', 'Накладная (Наложенный платеж)'),

            'logistic.plugin.id' => Translator::get('fields', 'Логистический плагин (ID)'),
            'logistic.plugin.name' => Translator::get('fields', 'Логистический плагин (Название)'),

            'logistic.status.code' => Translator::get('fields', 'Статус доставки (Код)'),
            'logistic.status.text' => Translator::get('fields', 'Статус доставки (Описание)'),
            'logistic.status.assignmentAt' => Translator::get('fields', 'Статус доставки (Время получения статуса)'),
            'logistic.status.office.address.postcode' => Translator::get('fields', 'Статус доставки (Офис, почтовый индекс)'),
            'logistic.status.office.address.region' => Translator::get('fields', 'Статус доставки (Офис, регион)'),
            'logistic.status.office.address.city' => Translator::get('fields', 'Статус доставки (Офис, город)'),
            'logistic.status.office.address.address_1' => Translator::get('fields', 'Статус доставки (Офис, адрес)'),
            'logistic.status.office.address.address_2' => Translator::get('fields', 'Статус доставки (Офис, адрес (дополнительный))'),
            'logistic.status.office.address.country' => Translator::get('fields', 'Статус доставки (Офис, страна)'),
            'logistic.status.office.phones' => Translator::get('fields', 'Статус доставки (Офис, контакты)'),
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
            'cart.total' => Translator::get('fields', 'Корзина (сумма)'),
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
  orderFieldsFetcher {
    fields {
      name
      label
      __typename
    }
  }
}

QUERY;

        $response = $this->client->query($query, [])->getData();

        $groups = [];
        foreach ($response['orderFieldsFetcher']['fields'] as $fieldData) {
            $name = $fieldData['name'];
            $label = $fieldData['label'];
            $typename = lcfirst($fieldData['__typename'] . 's');
            switch ($fieldData['__typename']) {
                case 'BooleanOrderField':
                    $key = Translator::get('fields', 'Логический (да/нет)');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'DatetimeOrderField':
                    $key = Translator::get('fields', 'Дата и время');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'EnumOrderField':
                    $key = Translator::get('fields', 'Списки');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'EmailOrderField':
                    $key = Translator::get('fields', 'Email');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value.raw" => $label,
                        "data.{$typename}.[field.name={$name}].value.duplicates" => Translator::get(
                            'fields',
                            '{label} (дублей)',
                            ['label' => $label]
                        ),
                    ];
                    break;
                case 'FileOrderField':
                    $key = Translator::get('fields', 'Файлы');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value.clientFileName" => Translator::get(
                            'fields',
                            '{label} (исходное имя файла)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.size" => Translator::get(
                            'fields',
                            '{label} (размер в байтах)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.uri" => Translator::get(
                            'fields',
                            '{label} (ссылка)',
                            ['label' => $label]
                        ),
                    ];
                    break;
                case 'FloatOrderField':
                    $key = Translator::get('fields', 'Дробные числа');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'ImageOrderField':
                    $key = Translator::get('fields', 'Изображения');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value.large.uri" => Translator::get(
                            'fields',
                            '{label} (большой размер)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.medium.uri" => Translator::get(
                            'fields',
                            '{label} (средний размер)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.small.uri" => Translator::get(
                            'fields',
                            '{label} (маленький размер)',
                            ['label' => $label]
                        ),
                    ];
                    break;
                case 'IntegerOrderField':
                    $key = Translator::get('fields', 'Целые числа');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'PhoneOrderField':
                    $key = Translator::get('fields', 'Телефоны');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value.phone.raw" => Translator::get(
                            'fields',
                            '{label} (исходный)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.phone.international" => Translator::get(
                            'fields',
                            '{label} (международный)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.phone.national" => Translator::get(
                            'fields',
                            '{label} (локальный)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.phone.country" => Translator::get(
                            'fields',
                            '{label} (код страны)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.duplicates" => Translator::get(
                            'fields',
                            '{label} (дублей)',
                            ['label' => $label]
                        ),
                    ];
                    break;
                case 'StringOrderField':
                    $key = Translator::get('fields', 'Строки');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'AddressOrderField':
                    $key = Translator::get('fields', 'Адреса');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value.postcode" => Translator::get(
                            'fields',
                            '{label} (почтовый индекс)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.region" => Translator::get(
                            'fields',
                            '{label} (регион)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.city" => Translator::get(
                            'fields',
                            '{label} (город)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.address_1" => Translator::get(
                            'fields',
                            '{label} (адрес 1)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.address_2" => Translator::get(
                            'fields',
                            '{label} (адрес 2)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.country" => Translator::get(
                            'fields',
                            '{label} (страна)',
                            ['label' => $label]
                        ),
                    ];
                    break;
                case 'HumanNameOrderField':
                    $key = Translator::get('fields', 'Ф.И.О');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value.firstName" => Translator::get(
                            'fields',
                            '{label} (имя)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.lastName" => Translator::get(
                            'fields',
                            '{label} (фамилия)',
                            ['label' => $label]
                        ),
                    ];
                    break;
                case 'UserOrderField':
                    $key = Translator::get('fields', 'Пользователи');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value.id" => Translator::get(
                            'fields',
                            '{label} (ID)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.email" => Translator::get(
                            'fields',
                            '{label} (email)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.name.firstName" => Translator::get(
                            'fields',
                            '{label} (имя)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.name.lastName" => Translator::get(
                            'fields',
                            '{label} (фамилия)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.role.id" => Translator::get(
                            'fields',
                            '{label} (роль, id)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.role.name" => Translator::get(
                            'fields',
                            '{label} (роль, название)',
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