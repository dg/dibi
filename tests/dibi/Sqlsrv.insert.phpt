<?php

/**
 * @dataProvider? ../databases.ini sqlsrv
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . '/data/sqlsrv.insert.sql');

for ($i = 1; $i <= 5; $i++) {
	$conn->query('INSERT INTO %n DEFAULT VALUES', 'aaa');
	Assert::equal($i, $conn->getInsertId());
}

$conn->query('INSERT INTO %n DEFAULT VALUES', 'aab');
Assert::equal(1, $conn->getInsertId());

$conn->query(
	'CREATE TRIGGER %n ON %n AFTER INSERT AS INSERT INTO %n DEFAULT VALUES',
	'UpdAAB',
	'aab',
	'aaa',
);

$conn->query('INSERT INTO %n DEFAULT VALUES', 'aab');
Assert::equal(2, $conn->getInsertId());
