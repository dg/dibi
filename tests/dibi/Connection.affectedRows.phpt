<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


$conn->query('INSERT INTO products', [
	'title' => 'Test product',
]);
Assert::same(1, $conn->getAffectedRows());


$conn->query('UPDATE products SET title="xxx" WHERE product_id > 100');
Assert::same(0, $conn->getAffectedRows());


$conn->query('UPDATE products SET title="xxx"');
Assert::same(4, $conn->getAffectedRows());


$conn->query('DELETE FROM orders');
$conn->query('DELETE FROM products WHERE product_id > 100');
Assert::same(0, $conn->getAffectedRows());


$conn->query('DELETE FROM products WHERE product_id < 3');
Assert::same(2, $conn->getAffectedRows());
