<?php

/**
 * @dataProvider ../databases.ini !=odbc, !=sqlsrv
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");

$res = $conn->query('
	SELECT products.product_id, orders.order_id, customers.name, products.product_id + 1 AS [xXx]
	FROM ([products]
	INNER JOIN [orders] ON [products.product_id] = [orders.product_id])
	INNER JOIN [customers] ON [orders.customer_id] = [customers.customer_id]
');

$info = $res->getInfo();


Assert::same(4, $res->getColumnCount());


Assert::same(
	['product_id', 'order_id', 'name', 'xXx'],
	$info->getColumnNames(),
);


if (!in_array($config['driver'], ['sqlite', 'sqlite3', 'pdo', 'sqlsrv'], true)) {
	Assert::same(
		['products.product_id', 'orders.order_id', 'customers.name', 'xXx'],
		$info->getColumnNames(true),
	);
}


$columns = $info->getColumns();

Assert::same('product_id', $columns[0]->getName());
if (!in_array($config['driver'], ['sqlite', 'sqlite3', 'pdo', 'sqlsrv'], true)) {
	Assert::same('products', $columns[0]->getTableName());
}
Assert::null($columns[0]->getVendorInfo('xxx'));
if (!in_array($config['system'], ['sqlite', 'sqlsrv'], true)) {
	Assert::same('i', $columns[0]->getType());
}
Assert::false($columns[0]->isNullable());

Assert::same('xXx', $columns[3]->getName());
Assert::null($columns[3]->getTableName());
if (!in_array($config['system'], ['sqlite', 'sqlsrv'], true)) {
	Assert::same('i', $columns[0]->getType());
}
Assert::false($columns[3]->isNullable());

Assert::same('xXx', $info->getColumn('xxx')->getName());
Assert::same('xXx', $info->getColumn('xXx')->getName());
