<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


$conn->query('INSERT INTO products', [
	'title' => 'Test product',
]);
Assert::same(1, $conn->getAffectedRows());


$res = $conn->query('UPDATE products SET title="xxx" WHERE product_id > 100');
Assert::same(0, $conn->getAffectedRows());
Assert::same(0, $res->getRowCount());


$res = $conn->query('UPDATE products SET title="xxx"');
Assert::same(4, $conn->getAffectedRows());
Assert::same(4, $res->getRowCount());


$conn->query('DELETE FROM orders');
$res = $conn->query('DELETE FROM products WHERE product_id > 100');
Assert::same(0, $conn->getAffectedRows());
Assert::same(0, $res->getRowCount());


$res = $conn->query('DELETE FROM products WHERE product_id < 3');
Assert::same(2, $conn->getAffectedRows());
Assert::same(2, $res->getRowCount());
