<?php

declare(strict_types=1);

use Dibi\Drivers\MySqliDriver;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

//$rc = new \mysqli(
//	$config['host'],
//	$config['username'],
//	$config['password'],
//	$config['database'],
//	$config['port'],
//);
$rc = new \mysqli(
	"127.0.0.1",
	'root',
	'root',
	'dibi_test',
	3306,
);

$driver = new MySqliDriver([
	'resource' => $rc,
]);

// sanity check
Assert::same($rc, $driver->getResource());

// close the connection
$rc->close();

// This would trigger an error in PHP 8, see #409
Assert::null($driver->getResource());
