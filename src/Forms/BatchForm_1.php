<?php
/**
 * Created for plugin-exporter-excel
 * Date: 06.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Excel\Forms;


use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Limit;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnumDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Settings\Settings;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Instance\Excel\ValuesList\FormatValues;

class BatchForm_1 extends Form
{

    public function __construct()
    {
        $settings = Settings::find();
        $defaultFormat = $settings->getData()->get('main.format');

        if (is_null($defaultFormat)) {
            $settings::guardIntegrity();
        }

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
                            $settings->getData()->get('main.format')
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