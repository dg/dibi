<?php

/**
 * Test: query exceptions.
 * @dataProvider ../databases.ini sqlite
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


$e = Assert::exception(function () use ($conn) {
	$conn->query('SELECT');
}, 'Dibi\DriverException', '%a% syntax error', 1);

Assert::same('SELECT', $e->getSql());


$e = Assert::exception(function () use ($conn) {
	$conn->query('INSERT INTO products (product_id, title) VALUES (1, "New")');
}, 'Dibi\UniqueConstraintViolationException', NULL, 19);

Assert::same("INSERT INTO products (product_id, title) VALUES (1, 'New')", $e->getSql());


$e = Assert::exception(function () use ($conn) {
	$conn->query('INSERT INTO products (title) VALUES (NULL)');
}, 'Dibi\NotNullConstraintViolationException', NULL, 19);

Assert::same('INSERT INTO products (title) VALUES (NULL)', $e->getSql());


$e = Assert::exception(function () use ($conn) {
	$conn->query('PRAGMA foreign_keys=true');
	$conn->query('INSERT INTO orders (customer_id, product_id, amount) VALUES (100, 1, 1)');
}, 'Dibi\ForeignKeyConstraintViolationException', NULL, 19);

Assert::same('INSERT INTO orders (customer_id, product_id, amount) VALUES (100, 1, 1)', $e->getSql());
