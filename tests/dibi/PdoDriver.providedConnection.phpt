<?php

/**
 * @dataProvider ../databases.ini != nothing, pdo
 */

// Background:
// When PDO connection is passed into Dibi it can be in (re)configured in various ways.
// This affects how connection is then internally handled.
// There should be no visible difference in Dibi behaviour regardless of PDO configuration (except unsupported configurations).


declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


function buildPDOConnection(int $errorMode = NULL): PDO {
	global $config;

	// used to parse config, establish connection
	$connection = new \Dibi\Connection($config);
	$dibiDriver = $connection->getDriver();
	\assert($dibiDriver instanceof \Dibi\Drivers\PdoDriver);

	// hack: extract PDO connection from driver (no public interface for that)
	$connectionProperty = (new ReflectionClass($dibiDriver))
		->getProperty('connection');
	$connectionProperty->setAccessible(TRUE);
	$pdo = $connectionProperty->getValue($dibiDriver);
	\assert($pdo instanceof PDO);

	// check that error reporting is in PHPs default value
	\assert($pdo->getAttribute(\PDO::ATTR_ERRMODE) === \PDO::ERRMODE_SILENT);

	// override PDO error mode if provided
	if ($errorMode !== NULL) {
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, $errorMode);
	}
	return $pdo;
}

/** @throws \Dibi\NotSupportedException */
function buildPdoDriverWithProvidedConnection(PDO $pdo): \Dibi\Drivers\PdoDriver {
	$driverConfig = ['resource' => $pdo];
	return new \Dibi\Drivers\PdoDriver($driverConfig);
}


// PDO error mode: exception
Assert::exception(function() {
	$pdoConnection = buildPDOConnection(\PDO::ERRMODE_EXCEPTION);
	buildPdoDriverWithProvidedConnection($pdoConnection);
}, \Dibi\DriverException::class, 'PDO connection in exception or warning error mode is currently not supported. Consider upgrading to Dibi >=4.1.0.');


// PDO error mode: warning
Assert::exception(function() {
	$pdoConnection = buildPDOConnection(\PDO::ERRMODE_WARNING);
	buildPdoDriverWithProvidedConnection($pdoConnection);
}, \Dibi\DriverException::class, 'PDO connection in exception or warning error mode is currently not supported. Consider upgrading to Dibi >=4.1.0.');


// PDO error mode: explicitly set silent
test(function() {
	$pdoConnection = buildPDOConnection(\PDO::ERRMODE_SILENT);
	Assert::type(\Dibi\Drivers\PdoDriver::class, buildPdoDriverWithProvidedConnection($pdoConnection));
});


// PDO error mode: implicitly set silent
test(function() {
	$pdoConnection = buildPDOConnection(NULL);
	Assert::type(\Dibi\Drivers\PdoDriver::class, buildPdoDriverWithProvidedConnection($pdoConnection));
});
