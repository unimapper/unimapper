<?php

$loader = @include __DIR__ . '/../vendor/autoload.php';
if (!$loader) {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

$loader->addPsr4("UniMapper\Tests\Fixtures\\", __DIR__ . "/fixtures");

Tester\Environment::setup();

$mockista = new \Mockista\Registry;