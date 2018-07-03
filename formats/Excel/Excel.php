<?php
/**
 * Created for lv-exports.
 * Datetime: 03.07.2018 12:53
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Export\Format\Excel;


use Leadvertex\External\Export\App\FormatDefinition;
use Leadvertex\External\Export\App\FieldDefinitions\ArrayDefinition;
use Leadvertex\External\Export\App\FieldDefinitions\DropdownDefinition;

class Excel extends FormatDefinition
{

    public function __construct()
    {
        parent::__construct(
            ['Excel'],
            [
                'en' => 'Export orders to excel file',
                'ru' => 'Выгружает заказы в excel файл',
            ],
            [
                'columns' => new ArrayDefinition(
                    [
                        'en' => 'Columns to export',
                        'ru' => 'Колонки для выгрузки',
                    ],
                    [
                        'en' => 'Columns with this order will be exported to excel table',
                        'ru' => 'Колонки будут выгружены в таблицу excel в заданной последовательности',
                    ],
                    ['firstName', 'lastName', 'phone'],
                    true,
                    ['firstName', 'lastName', 'phone', 'email', 'additional_1', 'additional_2']
                ),
                'format' => new DropdownDefinition(
                    [
                        'en' => 'File format',
                        'ru' => 'Формат файла',
                    ],
                    [
                        'en' => 'csv - simple plain-text format, xls - old excel 2003 format, xlsx - new excel format',
                        'ru' => 'csv - простой текстовый формат, xls - формат excel 2003, xlsx - новый формат excel',
                    ],
                    [
                        'csv' => [
                            'en' => '*.csv - simple plain text format',
                            'ru' => '*.csv - простой текстовый формат',
                        ],
                        'xls' => [
                            'en' => '*.xls - Excel 2003',
                            'ru' => '*.xls - Формат Excel 2003',
                        ],
                        'xlsx' => [
                            'en' => '*.xls - Excel 2007 and newer',
                            'ru' => '*.xls - Формат Excel 2007 и новее',
                        ],
                    ],
                    'csv',
                    true
                ),
            ]
        );
    }

}