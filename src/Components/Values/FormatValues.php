<?php
/**
 * Created for plugin-exporter-excel
 * Date: 04.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Excel\Components\Values;


use Leadvertex\Plugin\Components\Form\FieldDefinitions\FieldDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Values\StaticValues;
use Leadvertex\Plugin\Components\Form\FormData;
use Leadvertex\Plugin\Components\Translations\Translator;

class FormatValues extends StaticValues
{

    public function __construct()
    {
        parent::__construct([
            'xlsx' => [
                'title' => '(*.xlsx) Excel 2007+',
                'group' => Translator::get('format', 'Microsoft Office')
            ],
            'ods' => [
                'title' => '(*.ods) OpenDocument Spreadsheet',
                'group' => Translator::get('format', 'Другие')
            ],
            'csv' => [
                'title' => '(*.csv)',
                'group' => Translator::get('format', 'Другие')
            ],
        ]);
    }

    public function getValidator(): callable
    {
        return function ($value, FieldDefinition $definition, FormData $data) {
            if (!is_array($value) || count($value) <> 1) {
                return [Translator::get(
                    'errors',
                    'Некорректное значение'
                )];
            }

            $errors = [];
            $values = new FormatValues();
            if (!in_array($value[0], array_keys($values->get()))) {
                $errors[] = Translator::get(
                    'errors',
                    'Некорректный формат файла'
                );
            }

            return $errors;
        };
    }



}