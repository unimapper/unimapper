<?php

$loader = @require __DIR__ . '/../vendor/autoload.php';
if (!$loader) {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

Tester\Environment::setup();
Tester\Environment::$checkAssertions = false;

date_default_timezone_set("Europe/Prague");

class TestCase extends \Tester\TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }
}