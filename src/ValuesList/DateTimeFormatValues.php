<?php
/**
 * Created for plugin-exporter-excel
 * Date: 04.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Instance\Excel\ValuesList;


use SalesRender\Plugin\Components\Form\FieldDefinitions\FieldDefinition;
use SalesRender\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Values\StaticValues;
use SalesRender\Plugin\Components\Form\FormData;
use SalesRender\Plugin\Components\Translations\Translator;

class DateTimeFormatValues extends StaticValues
{

    public function __construct()
    {
        parent::__construct([
            'Y-m-d H:i:s (\U\T\C e)' => [
                'title' => "YYYY-MM-DD'T'HH:MM:SS+UTC offset",
                'group' => Translator::get('format', 'Форматы отображения даты и времени в Excel')
            ],
            'Y-m-d H:i' => [
                'title' => 'YYYY-MM-DD HH:MM',
                'group' => Translator::get('format', 'Форматы отображения даты и времени в Excel')
            ],
            'd.m.Y' => [
                'title' => 'DD.MM.YYYY',
                'group' => Translator::get('format', 'Форматы отображения даты и времени в Excel')
            ],
            'm-d-Y' => [
                'title' => 'MM-DD-YYYY',
                'group' => Translator::get('format', 'Форматы отображения даты и времени в Excel')
            ],
            'd-m-Y' => [
                'title' => 'DD-MM-YYYY',
                'group' => Translator::get('format', 'Форматы отображения даты и времени в Excel')
            ],
            'd/m/Y' => [
                'title' => 'DD/MM/YYYY',
                'group' => Translator::get('format', 'Форматы отображения даты и времени в Excel')
            ]
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
            $values = new DateTimeFormatValues();
            if (!in_array($value[0], array_keys($values->get()))) {
                $errors[] = Translator::get(
                    'errors',
                    'Некорректный формат даты'
                );
            }

            return $errors;
        };
    }



}