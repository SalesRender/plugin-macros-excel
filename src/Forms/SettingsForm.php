<?php
/**
 * Created for plugin-exporter-excel
 * Datetime: 03.03.2020 15:43
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace SalesRender\Plugin\Instance\Excel\Forms;


use SalesRender\Plugin\Components\Form\FieldDefinitions\BooleanDefinition;
use SalesRender\Plugin\Components\Form\FieldDefinitions\FieldDefinition;
use SalesRender\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Limit;
use SalesRender\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Values\StaticValues;
use SalesRender\Plugin\Components\Form\FieldDefinitions\ListOfEnumDefinition;
use SalesRender\Plugin\Components\Form\FieldGroup;
use SalesRender\Plugin\Components\Form\Form;
use SalesRender\Plugin\Components\Translations\Translator;
use SalesRender\Plugin\Instance\Excel\Components\Columns;
use SalesRender\Plugin\Instance\Excel\ValuesList\DateTimeFormatValues;
use SalesRender\Plugin\Instance\Excel\ValuesList\FormatValues;

class SettingsForm extends Form
{

    const FIELDS_DEFAULT = ['id', 'createdAt', 'cart.total'];
    const SHOW_HEADERS_DEFAULT = true;
    const FORMAT_DEFAULT = ['xlsx'];
    const DATE_TIME_FORMAT_DEFAULT = ['Y-m-d H:i:s (\U\T\C e)'];

    public function __construct()
    {
        $columns = new Columns();
        $format = new FormatValues();
        $dateTimeformat = new DateTimeFormatValues();
        parent::__construct(
            Translator::get(
                'settings',
                'UPLOAD_SETTINGS'
            ),
            Translator::get(
                'settings',
                'SETTINGS_DESCRIPTION'
            ),
            [
                'main' => new FieldGroup(
                    Translator::get('settings', 'BASIC_SETTINGS'),
                    null,
                    [
                        'headers' => new BooleanDefinition(
                            Translator::get(
                                'settings',
                                'COLUMN_HEADERS'
                            ),
                            Translator::get(
                                'settings',
                                'ADD_HEADERS'
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
                            self::SHOW_HEADERS_DEFAULT
                        ),
                        'fields' => new ListOfEnumDefinition(
                            Translator::get(
                                'settings',
                                'COLUMNS'
                            ),
                            Translator::get(
                                'settings',
                                'SELECT_DATA'
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
                            self::FIELDS_DEFAULT
                        ),
                        'format' => new ListOfEnumDefinition(
                            Translator::get(
                                'settings',
                                'DEFAULT_FILE_FORMAT'
                            ),
                            Translator::get(
                                'settings',
                                'SELECT_DEFAULT_FORMAT'
                            ),
                            $format->getValidator(),
                            $format,
                            new Limit(1, 1),
                            self::FORMAT_DEFAULT
                        ),
                        'dateTimeformat' => new ListOfEnumDefinition(
                            Translator::get(
                                'settings',
                                'DEFAULT_DATE_TIME_FORMAT'
                            ),
                            Translator::get(
                                'settings',
                                'Default file format'
                            ),
                            $dateTimeformat->getValidator(),
                            $dateTimeformat,
                            new Limit(1, 1),
                            self::DATE_TIME_FORMAT_DEFAULT
                        )
                    ]
                )
            ],
            Translator::get(
                'settings',
                'SAVE_SETTINGS'
            )
        );
    }

}