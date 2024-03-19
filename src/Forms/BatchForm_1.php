<?php
/**
 * Created for plugin-exporter-excel
 * Date: 06.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Instance\Excel\Forms;


use SalesRender\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Limit;
use SalesRender\Plugin\Components\Form\FieldDefinitions\ListOfEnumDefinition;
use SalesRender\Plugin\Components\Form\FieldGroup;
use SalesRender\Plugin\Components\Form\Form;
use SalesRender\Plugin\Components\Settings\Settings;
use SalesRender\Plugin\Components\Translations\Translator;
use SalesRender\Plugin\Instance\Excel\ValuesList\FormatValues;

class BatchForm_1 extends Form
{

    public function __construct()
    {
        $settings = Settings::find();

        $format = new FormatValues();
        parent::__construct(
            Translator::get(
                'options',
                'Выгрузка Excel'
            ),
            null,
            [
                'options' => new FieldGroup(
                    Translator::get('settings', 'Параметры выгрузки'),
                    null,
                    [
                        'format' => new ListOfEnumDefinition(
                            Translator::get(
                                'settings',
                                'Формат файла'
                            ),
                            Translator::get(
                                'settings',
                                'Выберите формат, в котором вы хотите получить выгруженные данные'
                            ),
                            $format->getValidator(),
                            $format,
                            new Limit(1, 1),
                            $settings->getData()->get('main.format', SettingsForm::FORMAT_DEFAULT)
                        ),
                    ]
                )
            ],
            Translator::get(
                'settings',
                'Выгрузка'
            )
        );
    }

}