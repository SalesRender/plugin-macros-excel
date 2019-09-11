<?php
use Leadvertex\Plugin\Handler\Factories\AppFactory;

require_once __DIR__ . '/../const.php';

$factory = new AppFactory();
$application = $factory->web();
$application->run();