<?php

/**
 * @dataProvider? ../databases.ini sqlsrv
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// https://docs.microsoft.com/en-us/sql/connect/php/how-to-specify-php-data-types?view=sql-server-ver15
$tests = function ($conn) {
	$driver = $conn->getDriver();

	Assert::equal('hello', $conn->query('SELECT %tsql', $driver->bindAsciiText('hello', '20'))->fetchSingle());

	Assert::equal(1, $conn->query('INSERT INTO Customers ([name]) VALUES (%tsql)', $driver->bindText('❤️‍🔥'))->getRowCount());
	Assert::equal('❤️‍🔥', $conn->fetchSingle('SELECT [name] FROM Customers WHERE [name] = %tsql', $driver->bindText('❤️‍🔥')));

	$param = $driver->bindText('testing', '20', 'UTF-8');
	Assert::equal(\SQLSRV_SQLTYPE_NVARCHAR('20'), $param->sqlType);
	Assert::equal('testing', $param->value);
	Assert::equal(\SQLSRV_PHPTYPE_STRING('UTF-8'), $param->phpType);

	Assert::equal('?????', $conn->query('SELECT %tsql', $driver->bindAsciiText('❤️‍🔥'))->fetchSingle());

	Assert::equal('❤️‍🔥', $conn->query('SELECT %tsql', $driver->bindText('❤️‍🔥'))->fetchSingle());

	Assert::equal(42, $conn->query('SELECT %tsql', $driver->bindInt(42))->fetchSingle());

	Assert::equal(null, $conn->query('SELECT %tsql', $driver->bindText(null))->fetchSingle());
};

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");

$tests($conn);
