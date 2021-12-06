<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


function buildPdoDriver(?int $errorMode)
{
	$pdo = new PDO('sqlite::memory:');
	if ($errorMode !== null) {
		$pdo->setAttribute(PDO::ATTR_ERRMODE, $errorMode);
	}
	new Dibi\Drivers\PdoDriver(['resource' => $pdo]);
}


// PDO error mode: exception
Assert::exception(function () {
	buildPdoDriver(PDO::ERRMODE_EXCEPTION);
}, Dibi\DriverException::class, 'PDO connection in exception or warning error mode is not supported.');


// PDO error mode: warning
Assert::exception(function () {
	buildPdoDriver(PDO::ERRMODE_WARNING);
}, Dibi\DriverException::class, 'PDO connection in exception or warning error mode is not supported.');


test('PDO error mode: explicitly set silent', function () {
	buildPdoDriver(PDO::ERRMODE_SILENT);
});
