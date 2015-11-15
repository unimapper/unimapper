<?php

use UniMapper\NamingConvention as UNC;

$loader = @require __DIR__ . '/../vendor/autoload.php';
if (!$loader) {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

Tester\Environment::setup();
Tester\Environment::$checkAssertions = false;

date_default_timezone_set("Europe/Prague");

UNC::setMask("*", UNC::ENTITY_MASK);
UNC::setMask("*Repository", UNC::REPOSITORY_MASK);

class TestCase extends \Tester\TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }
}