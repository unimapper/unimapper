<?php

if (@!include __DIR__ . '/../../../autoload.php') {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

Tester\Environment::setup();

require __DIR__ . '/common/Entities.php';
require __DIR__ . '/common/Mappers.php';

$mockista = new \Mockista\Registry;