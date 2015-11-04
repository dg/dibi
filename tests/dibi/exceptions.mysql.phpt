<?php

/**
 * Test: query exceptions.
 * @dataProvider? ../databases.ini mysql
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


$e = Assert::exception(function () use ($conn) {
	$conn->query('SELECT');
}, 'Dibi\DriverException', "%a% error in your SQL syntax;%a%", 1064);

Assert::same('SELECT', $e->getSql());


$e = Assert::exception(function () use ($conn) {
	$conn->query('INSERT INTO products (product_id, title) VALUES (1, "New")');
}, 'Dibi\UniqueConstraintViolationException', "%a?%Duplicate entry '1' for key 'PRIMARY'", 1062);

Assert::same("INSERT INTO products (product_id, title) VALUES (1, 'New')", $e->getSql());


$e = Assert::exception(function () use ($conn) {
	$conn->query('INSERT INTO products (title) VALUES (NULL)');
}, 'Dibi\NotNullConstraintViolationException', "%a?%Column 'title' cannot be null", 1048);

Assert::same('INSERT INTO products (title) VALUES (NULL)', $e->getSql());


$e = Assert::exception(function () use ($conn) {
	$conn->query('INSERT INTO orders (customer_id, product_id, amount) VALUES (100, 1, 1)');
}, 'Dibi\ForeignKeyConstraintViolationException', '%a% a foreign key constraint fails %a%', 1452);

Assert::same('INSERT INTO orders (customer_id, product_id, amount) VALUES (100, 1, 1)', $e->getSql());
