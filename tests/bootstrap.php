<?php

use UniMapper\NamingConvention as UNC;

$loader = @require __DIR__ . '/../vendor/autoload.php';
if (!$loader) {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}
$loader->addPsr4("UniMapper\Tests\\", __DIR__);

Tester\Environment::setup();
Tester\Environment::$checkAssertions = false;

date_default_timezone_set("Europe/Prague");

UNC::setMask("UniMapper\Tests\Fixtures\Entity\*", UNC::ENTITY_MASK);
UNC::setMask("UniMapper\Tests\Fixtures\Repository\*Repository", UNC::REPOSITORY_MASK);