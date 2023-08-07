<?php

use Leadvertex\Plugin\Components\Batch\BatchContainer;
use Leadvertex\Plugin\Components\Db\Components\Connector;
use Leadvertex\Plugin\Components\Info\Developer;
use Leadvertex\Plugin\Components\Info\Info;
use Leadvertex\Plugin\Components\Info\PluginType;
use Leadvertex\Plugin\Components\Purpose\PluginClass;
use Leadvertex\Plugin\Components\Purpose\PluginEntity;
use Leadvertex\Plugin\Components\Purpose\PluginPurpose;
use Leadvertex\Plugin\Components\Settings\Settings;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Instance\Excel\ExcelHandler;
use Leadvertex\Plugin\Instance\Excel\Forms\BatchForm_1;
use Leadvertex\Plugin\Instance\Excel\Forms\SettingsForm;
use Medoo\Medoo;
use XAKEPEHOK\Path\Path;

require_once __DIR__ . '/vendor/autoload.php';

# 1. Configure DB (for SQLite *.db file and parent directory should be writable)
Connector::config(new Medoo([
    'database_type' => $_ENV['DATABASE_TYPE'],
    'server' => $_ENV['DATABASE_SERVER'],
    'database_name' => $_ENV['DATABASE_NAME'],
    'username' => $_ENV['DATABASE_USER'],
    'password' => $_ENV['DATABASE_PASS']
]));

# 2. Set plugin default language
Translator::config('ru_RU');

# 3. Configure info about plugin
Info::config(
    new PluginType(PluginType::MACROS),
    fn() => Translator::get('info', 'Excel'),
    fn() => Translator::get('info', 'Позволяет осуществлять выгрузку заказов в Excel'),
    new PluginPurpose(
        new PluginClass(PluginClass::CLASS_EXPORTER),
        new PluginEntity(PluginEntity::ENTITY_ORDER)
    ),
    new Developer(
        'LeadVertex',
        'support@leadvertex.com',
        'leadvertex.com',
    )
);

# 4. Configure settings form
Settings::setForm(fn() => new SettingsForm());

# 6. Configure batch forms and handler
BatchContainer::config(
    function (int $number) {
        switch ($number) {
            case 1: return new BatchForm_1();
            default: return null;
        }
    },
    new ExcelHandler()
);