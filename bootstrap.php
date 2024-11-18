<?php

use SalesRender\Plugin\Components\Batch\BatchContainer;
use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Info\Developer;
use SalesRender\Plugin\Components\Info\Info;
use SalesRender\Plugin\Components\Info\PluginType;
use SalesRender\Plugin\Components\Purpose\MacrosPluginClass;
use SalesRender\Plugin\Components\Purpose\PluginEntity;
use SalesRender\Plugin\Components\Purpose\PluginPurpose;
use SalesRender\Plugin\Components\Settings\Settings;
use SalesRender\Plugin\Components\Translations\Translator;
use SalesRender\Plugin\Instance\Excel\ExcelHandler;
use SalesRender\Plugin\Instance\Excel\Forms\BatchForm_1;
use SalesRender\Plugin\Instance\Excel\Forms\SettingsForm;
use Medoo\Medoo;
use XAKEPEHOK\Path\Path;

if (!empty($_ENV['APP_SENTRY'])) {
    \Sentry\init(['dsn' => $_ENV['APP_SENTRY']]);
}

# 1. Configure DB (for SQLite *.db file and parent directory should be writable)
switch ($_ENV['DATABASE_TYPE'] ?? 'sqlite') {
    default:
    case 'sqlite':
        $params = [
            'database_type' => 'sqlite',
            'database_file' => Path::root()->down('db/database.db')
        ];
        break;
    case 'mysql':
        $params = [
            'database_type' => 'mysql',
            'server' => $_ENV['DATABASE_SERVER'],
            'database_name' => $_ENV['DATABASE_NAME'],
            'username' => $_ENV['DATABASE_USER'],
            'password' => $_ENV['DATABASE_PASS']
        ];
        break;
}
Connector::config(new Medoo($params));

# 2. Set plugin default language
Translator::config('ru_RU');

# 3. Configure info about plugin
Info::config(
    new PluginType(PluginType::MACROS),
    fn() => Translator::get('info', 'INFO'),
    fn() => Translator::get('info', 'DESC'),
    new PluginPurpose(
        new MacrosPluginClass(MacrosPluginClass::CLASS_EXPORTER),
        new PluginEntity(PluginEntity::ENTITY_ORDER)
    ),
    new Developer(
        'SalesRender',
        'support@salesrender.com',
        'salesrender.com',
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