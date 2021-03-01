<?php

/**
 * Test: query exceptions.
 * @dataProvider? ../databases.ini mysql
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


$e = Assert::exception(
	fn() => new Dibi\Connection([
		'driver' => 'mysqli',
		'host' => 'localhost',
		'username' => 'unknown',
		'password' => 'unknown',
	]),
	Dibi\DriverException::class,
);

Assert::null($e->getSql());


$e = Assert::exception(
	fn() => $conn->query('SELECT'),
	Dibi\DriverException::class,
	'%a% error in your SQL syntax;%a%',
	1064,
);

Assert::same('SELECT', $e->getSql());


$e = Assert::exception(
	fn() => $conn->query('INSERT INTO products (product_id, title) VALUES (1, "New")'),
	Dibi\UniqueConstraintViolationException::class,
	"%a?%Duplicate entry '1' for key '%a?%PRIMARY'",
	1062,
);

Assert::same("INSERT INTO products (product_id, title) VALUES (1, 'New')", $e->getSql());


$e = Assert::exception(
	fn() => $conn->query('INSERT INTO products (title) VALUES (NULL)'),
	Dibi\NotNullConstraintViolationException::class,
	"%a?%Column 'title' cannot be null",
	1048,
);

Assert::same('INSERT INTO products (title) VALUES (NULL)', $e->getSql());


$e = Assert::exception(
	fn() => $conn->query('INSERT INTO orders (customer_id, product_id, amount) VALUES (100, 1, 1)'),
	Dibi\ForeignKeyConstraintViolationException::class,
	'%a% a foreign key constraint fails %a%',
	1452,
);

Assert::same('INSERT INTO orders (customer_id, product_id, amount) VALUES (100, 1, 1)', $e->getSql());
