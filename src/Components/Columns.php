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
            $token->getBackendUri() . 'companies/stark-industries/CRM',
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
  fieldsFetcher {
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
        foreach ($response['fieldsFetcher']['fields'] as $fieldData) {
            $name = $fieldData['name'];
            $label = $fieldData['label'];
            $typename = lcfirst($fieldData['__typename'] . 's');
            switch ($fieldData['__typename']) {
                case 'BooleanField':
                    $key = Translator::get('fields', 'Логический (да/нет)');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'DatetimeField':
                    $key = Translator::get('fields', 'Дата и время');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'EnumField':
                    $key = Translator::get('fields', 'Списки');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'EmailField':
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
                case 'FileField':
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
                case 'FloatField':
                    $key = Translator::get('fields', 'Дробные числа');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'ImageField':
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
                case 'IntegerField':
                    $key = Translator::get('fields', 'Целые числа');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'PhoneField':
                    $key = Translator::get('fields', 'Телефоны');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value.raw" => Translator::get(
                            'fields',
                            '{label} (исходный)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.international" => Translator::get(
                            'fields',
                            '{label} (международный)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.national" => Translator::get(
                            'fields',
                            '{label} (локальный)',
                            ['label' => $label]
                        ),
                        "data.{$typename}.[field.name={$name}].value.country" => Translator::get(
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
                case 'StringField':
                    $key = Translator::get('fields', 'Строки');
                    if (!key_exists($key, $groups)) {
                        $groups[$key] = [];
                    }
                    $groups[$key] += [
                        "data.{$typename}.[field.name={$name}].value" => $label,
                    ];
                    break;
                case 'AddressField':
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
                    ];
                    break;
                case 'HumanNameField':
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
                case 'UserField':
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