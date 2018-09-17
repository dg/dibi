<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


function buildPdoDriver($errorMode)
{
	$pdo = new PDO('sqlite::memory:');
	if ($errorMode !== null) {
		$pdo->setAttribute(PDO::ATTR_ERRMODE, $errorMode);
	}
	$config = ['resource' => $pdo];
	$driver = new Dibi\Drivers\PdoDriver;
	$driver->connect($config);
}


// PDO error mode: exception
Assert::exception(function () {
	buildPdoDriver(PDO::ERRMODE_EXCEPTION);
}, 'Dibi\DriverException', 'PDO connection in exception or warning error mode is not supported.');


// PDO error mode: warning
Assert::exception(function () {
	buildPdoDriver(PDO::ERRMODE_WARNING);
}, 'Dibi\DriverException', 'PDO connection in exception or warning error mode is not supported.');


// PDO error mode: explicitly set silent
test(function () {
	buildPdoDriver(PDO::ERRMODE_SILENT);
});


// PDO error mode: implicitly set silent
test(function () {
	buildPdoDriver(null);
});
