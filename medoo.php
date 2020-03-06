<?php

use Leadvertex\Plugin\Components\Db\Components\Connector;
use Medoo\Medoo;

require_once __DIR__ . '/vendor/autoload.php';

Connector::init(new Medoo([
    'database_type' => 'sqlite',
    'database_file' => __DIR__ . '/database.db' //file will be created if not exists
]));