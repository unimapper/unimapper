<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

Tester\Environment::setup();

date_default_timezone_set('Europe/Prague');

require __DIR__ . '/common/Entities.php';
require __DIR__ . '/common/Mappers.php';