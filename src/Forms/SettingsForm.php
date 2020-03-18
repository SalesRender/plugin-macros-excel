<?php
/**
 * Created for plugin-exporter-excel
 * Datetime: 03.03.2020 15:43
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Instance\Macros\Forms;


use Leadvertex\Plugin\Components\Form\FieldDefinitions\BooleanDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\FieldDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Limit;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Values\StaticValues;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnumDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Instance\Macros\Components\Columns;
use Leadvertex\Plugin\Instance\Macros\Components\Values\FormatValues;

class SettingsForm extends Form
{

    public function __construct()
    {
        $columns = new Columns();
        $format = new FormatValues();
        parent::__construct(
            Translator::get(
                'settings',
                'Настройки выгрузки в Excel'
            ),
            Translator::get(
                'settings',
                'Здесь вы можете настроить и порядок набор столбцов, которые будут выгружены в Excel'
            ),
            [
                'main' => new FieldGroup(
                    Translator::get('settings', 'Основные настройки'),
                    null,
                    [
                        'headers' => new BooleanDefinition(
                            Translator::get(
                                'settings',
                                'Заголовки столбцов'
                            ),
                            Translator::get(
                                'settings',
                                'Добавляет в начало документа строку с заголовками для каждого столбца'
                            ),
                            function ($value, FieldDefinition $definition) {
                                $errors = [];
                                if (!is_bool($value) && !is_int($value)) {
                                    $errors[] = Translator::get(
                                        'errors',
                                        'Некорректное значение поля {field}',
                                        ['field' => $definition->getTitle()]
                                    );
                                }
                                return $errors;
                            },
                            true
                        ),
                        'fields' => new ListOfEnumDefinition(
                            Translator::get(
                                'settings',
                                'Столбцы'
                            ),
                            Translator::get(
                                'settings',
                                'Выберите данные, которые вы хотите выгружать'
                            ),
                            function ($values) use ($columns) {
                                if (!is_array($values)) {
                                    return [Translator::get(
                                        'errors',
                                        'Некорректное значение'
                                    )];
                                }

                                $errors = [];
                                if (count($values) < 1) {
                                    $errors[] = Translator::get(
                                        'errors',
                                        'Необходимо выбрать минимум одно поле для выгрузки'
                                    );
                                }

                                foreach ($values as $value) {
                                    if (!isset($columns->getList()[$value])) {
                                        $errors[] = Translator::get(
                                            'errors',
                                            'Выбрано несуществующее поле "{field}"',
                                            ['field' => $value]
                                        );
                                    }
                                }

                                return $errors;
                            },
                            new StaticValues($columns->getList()),
                            new Limit(1, null),
                            ['id', 'createdAt', 'cart.totalPrice']
                        ),
                        'format' => new ListOfEnumDefinition(
                            Translator::get(
                                'settings',
                                'Формат файла по-умолчанию'
                            ),
                            Translator::get(
                                'settings',
                                'Выберите формат по-умолчанию, в котором вы хотите получить выгруженные данные'
                            ),
                            $format->getValidator(),
                            $format,
                            new Limit(1, 1),
                            ['xlsx']
                        ),
                    ]
                )
            ]
        );
    }

}