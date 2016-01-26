<?php

/**
 * @dataProvider ../databases.ini !=odbc, !=sqlsrv
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");

$info = $conn->query('
	SELECT products.product_id, orders.order_id, customers.name, products.product_id + 1 AS [xXx]
	FROM ([products]
	INNER JOIN [orders] ON [products.product_id] = [orders.product_id])
	INNER JOIN [customers] ON [orders.customer_id] = [customers.customer_id]
')->getInfo();


Assert::same(
	['product_id', 'order_id', 'name', 'xXx'],
	$info->getColumnNames()
);


if (!in_array($config['driver'], ['sqlite3', 'pdo', 'sqlsrv'])) {
	Assert::same(
		['products.product_id', 'orders.order_id', 'customers.name', 'xXx'],
		$info->getColumnNames(TRUE)
	);
}


$columns = $info->getColumns();

Assert::same('product_id', $columns[0]->getName());
if (!in_array($config['driver'], ['sqlite3', 'pdo', 'sqlsrv'])) {
	Assert::same('products', $columns[0]->getTableName());
}
Assert::null($columns[0]->getVendorInfo('xxx'));
if (!in_array($config['system'], ['sqlite', 'sqlsrv'])) {
	Assert::same('i', $columns[0]->getType());
}
Assert::null($columns[0]->isNullable());

Assert::same('xXx', $columns[3]->getName());
Assert::null($columns[3]->getTableName());
if (!in_array($config['system'], ['sqlite', 'sqlsrv'])) {
	Assert::same('i', $columns[0]->getType());
}
Assert::null($columns[3]->isNullable());

Assert::same('xXx', $info->getColumn('xxx')->getName());
Assert::same('xXx', $info->getColumn('xXx')->getName());
