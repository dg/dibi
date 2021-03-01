<?php

/**
 * Test: query exceptions.
 * @dataProvider ../databases.ini sqlite
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


$e = Assert::exception(
	fn() => $conn->query('SELECT'),
	Dibi\DriverException::class,
	'%a%',
	1,
);

Assert::same('SELECT', $e->getSql());


$e = Assert::exception(
	fn() => $conn->query('INSERT INTO products (product_id, title) VALUES (1, "New")'),
	Dibi\UniqueConstraintViolationException::class,
	null,
	19,
);

Assert::same("INSERT INTO products (product_id, title) VALUES (1, 'New')", $e->getSql());


$e = Assert::exception(
	fn() => $conn->query('INSERT INTO products (title) VALUES (NULL)'),
	Dibi\NotNullConstraintViolationException::class,
	null,
	19,
);

Assert::same('INSERT INTO products (title) VALUES (NULL)', $e->getSql());


$e = Assert::exception(function () use ($conn) {
	$conn->query('PRAGMA foreign_keys=true');
	$conn->query('INSERT INTO orders (customer_id, product_id, amount) VALUES (100, 1, 1)');
}, Dibi\ForeignKeyConstraintViolationException::class, null, 19);

Assert::same('INSERT INTO orders (customer_id, product_id, amount) VALUES (100, 1, 1)', $e->getSql());
