<?php declare(strict_types=1);

use Nette\PHPStan\Tester\TypeAssert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

TypeAssert::assertTypes(__DIR__ . '/dibi-types.php');
