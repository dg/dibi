<?php

/**
 * @dataProvider ../databases.ini != no match, pdo
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

/** @throws \Dibi\NotSupportedException */
function buildPdoDriver(PDO $pdo): \Dibi\Drivers\PdoDriver {
	$driverConfig = ['resource' => $pdo];
	return new \Dibi\Drivers\PdoDriver($driverConfig);
}

// PDO error mode: exception
Assert::exception(function() use ($config) {
	($pdoConnection = new PDO($config['dsn']))->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	buildPdoDriver($pdoConnection);
}, \Dibi\DriverException::class, 'PDO connection in exception or warning error mode is currently not supported. Consider upgrading to Dibi >=4.1.0.');


// PDO error mode: warning
Assert::exception(function() use ($config) {
	($pdoConnection = new PDO($config['dsn']))->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
	buildPdoDriver($pdoConnection);
}, \Dibi\DriverException::class, 'PDO connection in exception or warning error mode is currently not supported. Consider upgrading to Dibi >=4.1.0.');


// PDO error mode: explicitly set silent
test(function() use ($config) {
	($pdoConnection = new PDO($config['dsn']))->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
	Assert::type(\Dibi\Drivers\PdoDriver::class, buildPdoDriver($pdoConnection));
});


// PDO error mode: implicitly set silent
test(function() use ($config) {
	$pdoConnection = new PDO($config['dsn']);
	Assert::type(\Dibi\Drivers\PdoDriver::class, buildPdoDriver($pdoConnection));
});
