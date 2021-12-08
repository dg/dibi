<?php

declare(strict_types=1);

use Dibi\Helpers;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::same(0, Helpers::intVal(0));
Assert::same(0, Helpers::intVal('0'));
Assert::same(-10, Helpers::intVal('-10'));

Assert::exception(function () {
	Helpers::intVal('12345678901234567890123456879');
}, Dibi\Exception::class, 'Number 12345678901234567890123456879 is greater than integer.');

Assert::exception(function () {
	Helpers::intVal('-12345678901234567890123456879');
}, Dibi\Exception::class, 'Number -12345678901234567890123456879 is greater than integer.');

Assert::exception(function () {
	Helpers::intVal('');
}, Dibi\Exception::class, "Expected number, '' given.");

Assert::exception(function () {
	Helpers::intVal('not number');
}, Dibi\Exception::class, "Expected number, 'not number' given.");

Assert::exception(function () {
	Helpers::intVal(null);
}, Dibi\Exception::class, "Expected number, '' given.");
