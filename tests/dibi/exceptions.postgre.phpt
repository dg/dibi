<?php

/**
 * Test: query exceptions.
 * @dataProvider? ../databases.ini postgre
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


$e = Assert::exception(function () use ($conn) {
	$conn->query('SELECT INTO');
}, Dibi\DriverException::class, '%a?%syntax error %A%');

Assert::same('SELECT INTO', $e->getSql());


$e = Assert::exception(function () use ($conn) {
	$conn->query('INSERT INTO products (product_id, title) VALUES (1, "New")');
}, Dibi\UniqueConstraintViolationException::class, '%a% violates unique constraint %A%', '23505');

Assert::same("INSERT INTO products (product_id, title) VALUES (1, 'New')", $e->getSql());


$e = Assert::exception(function () use ($conn) {
	$conn->query('INSERT INTO products (title) VALUES (NULL)');
}, Dibi\NotNullConstraintViolationException::class, '%a?%null value in column "title"%a%violates not-null constraint%A?%', '23502');

Assert::same('INSERT INTO products (title) VALUES (NULL)', $e->getSql());


$e = Assert::exception(function () use ($conn) {
	$conn->query('INSERT INTO orders (customer_id, product_id, amount) VALUES (100, 1, 1)');
}, Dibi\ForeignKeyConstraintViolationException::class, '%a% violates foreign key constraint %A%', '23503');

Assert::same('INSERT INTO orders (customer_id, product_id, amount) VALUES (100, 1, 1)', $e->getSql());
