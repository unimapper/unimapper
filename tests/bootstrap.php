<?php

$loader = @include __DIR__ . '/../vendor/autoload.php';
if (!$loader) {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

// @todo
//$loader->addPsr4("UniMapper\Tests\Fixtures\\", __DIR__ . "/fixtures");

require __DIR__ . "/TestCase.php";
require __DIR__ . "/fixtures/cache/CustomCache.php";
require __DIR__ . "/fixtures/entity/NoAdapter.php";
require __DIR__ . "/fixtures/entity/Simple.php";
require __DIR__ . "/fixtures/entity/Nested.php";
require __DIR__ . "/fixtures/entity/Remote.php";
require __DIR__ . "/fixtures/entity/DuplicateProperty.php";
require __DIR__ . "/fixtures/entity/DuplicatePublicProperty.php";
require __DIR__ . "/fixtures/entity/NoPrimary.php";
require __DIR__ . "/fixtures/entity/NoProperty.php";
require __DIR__ . "/fixtures/repository/SimpleRepository.php";
require __DIR__ . "/fixtures/repository/NoPrimaryRepository.php";
require __DIR__ . "/fixtures/query/Custom.php";

Tester\Environment::setup();

date_default_timezone_set('Europe/Prague');

UniMapper\NamingConvention::$entityMask = "UniMapper\Tests\Fixtures\Entity\*";
UniMapper\NamingConvention::$repositoryMask = "UniMapper\Tests\Fixtures\Repository\*Repository";