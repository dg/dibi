<?php

/**
 * @dataProvider ../databases.ini !=odbc
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new DibiConnection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");

$info = $conn->query('
	SELECT products.product_id, orders.order_id, customers.name, products.product_id + 1 AS [xXx]
	FROM products
	INNER JOIN orders USING (product_id)
	INNER JOIN customers USING (customer_id)
')->getInfo();


Assert::same(
	array('product_id', 'order_id', 'name', 'xXx'),
	$info->getColumnNames()
);


if ($config['driver'] !== 'sqlite3' && $config['driver'] !== 'pdo') {
	Assert::same(
		array('products.product_id', 'orders.order_id', 'customers.name', 'xXx'),
		$info->getColumnNames(TRUE)
	);
}


$columns = $info->getColumns();

Assert::same('product_id', $columns[0]->name);
if ($config['driver'] !== 'sqlite3' && $config['driver'] !== 'pdo') {
	Assert::same('products', $columns[0]->tableName);
}
Assert::null($columns[0]->getVendorInfo('xxx'));
if ($config['system'] !== 'sqlite') {
	Assert::same('i', $columns[0]->type);
}
Assert::null($columns[0]->nullable);

Assert::same('xXx', $columns[3]->name);
Assert::null($columns[3]->tableName);
if ($config['system'] !== 'sqlite') {
	Assert::same('i', $columns[0]->type);
}
Assert::null($columns[3]->nullable);

Assert::same('xXx', $info->getColumn('xxx')->getName());
Assert::same('xXx', $info->getColumn('xXx')->getName());
