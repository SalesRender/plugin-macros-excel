<?php
/**
 * Created for plugin-exporter-excel
 * Date: 06.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Macros\Excel\Forms;


use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Limit;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnumDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Core\Macros\Models\Session;
use Leadvertex\Plugin\Instance\Macros\Excel\Components\Values\FormatValues;

class OptionsForm extends Form
{

    public function __construct()
    {
        $settings = Session::current()->getSettings();
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
                            [$settings->getData()->get('main.format')]
                        ),
                    ]
                )
            ]
        );
    }

}