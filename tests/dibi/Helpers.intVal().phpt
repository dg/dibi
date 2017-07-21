<?php

use Dibi\Helpers;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::same(0, Helpers::intVal(0));
Assert::same(0, Helpers::intVal('0'));
Assert::same(-10, Helpers::intVal('-10'));
Assert::same('12345678901234567890123456879', Helpers::intVal('12345678901234567890123456879'));
Assert::same('-12345678901234567890123456879', Helpers::intVal('-12345678901234567890123456879'));

Assert::exception(function () {
	Helpers::intVal('');
}, 'Dibi\Exception', "Expected number, '' given.");

Assert::exception(function () {
	Helpers::intVal('not number');
}, 'Dibi\Exception', "Expected number, 'not number' given.");

Assert::exception(function () {
	Helpers::intVal(null);
}, 'Dibi\Exception', "Expected number, '' given.");
