<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

if ($config['system'] === 'odbc') {
	Tester\Environment::skip('Not supported.');
}

$conn = new DibiConnection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");

$info = $conn->query('
	SELECT products.product_id, orders.order_id, customers.name, products.product_id + 1 AS xxx
	FROM products
	INNER JOIN orders USING (product_id)
	INNER JOIN customers USING (customer_id)
')->getInfo();


Assert::same(
	['product_id', 'order_id', 'name', 'xxx'],
	$info->getColumnNames()
);


if ($config['driver'] !== 'sqlite3' && $config['driver'] !== 'pdo') {
	Assert::same(
		['products.product_id', 'orders.order_id', 'customers.name', 'xxx'],
		$info->getColumnNames(TRUE)
	);
}


$columns = $info->getColumns();

Assert::same('product_id', $columns[0]->getName());
if ($config['driver'] !== 'sqlite3' && $config['driver'] !== 'pdo') {
	Assert::same('products', $columns[0]->getTableName());
}
Assert::null($columns[0]->getVendorInfo('xxx'));
if ($config['system'] !== 'sqlite') {
	Assert::same('i', $columns[0]->getType());
}
Assert::null($columns[0]->isNullable());

Assert::same('xxx', $columns[3]->getName());
Assert::null($columns[3]->getTableName());
if ($config['system'] !== 'sqlite') {
	Assert::same('i', $columns[0]->getType());
}
Assert::null($columns[3]->isNullable());
