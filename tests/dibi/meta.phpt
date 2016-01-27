<?php

/**
 * @dataProvider ../databases.ini !=odbc
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");

try {
	$meta = $conn->getDatabaseInfo();
} catch (Dibi\NotSupportedException $e) {
	Tester\Environment::skip($e->getMessage());
}

if ($config['system'] !== 'sqlsrv') {
	Assert::same(3, count($meta->getTables()));
	$names = $meta->getTableNames();
	sort($names);
	Assert::equal(['customers', 'orders', 'products'], $names);
}

Assert::false($meta->hasTable('xxxx'));

$table = $meta->getTable('products');
Assert::same('products', $table->getName());
Assert::false($table->isView());

Assert::same(2, count($table->getColumns()));
Assert::false($table->hasColumn('xxxx'));
Assert::true($table->hasColumn('product_id'));
Assert::true($table->hasColumn('Product_id'));
Assert::same('product_id', $table->getColumn('Product_id')->getName());

$column = $table->getColumn('product_id');
Assert::same('product_id', $column->getName());
Assert::same('products', $column->getTable()->getName());
Assert::same('i', $column->getType());
Assert::type('string', $column->getNativeType());
Assert::false($column->isNullable());
Assert::true($column->isAutoIncrement());

$column = $table->getColumn('title');
Assert::same('title', $column->getName());
Assert::same('products', $column->getTable()->getName());
Assert::same('s', $column->getType());
Assert::type('string', $column->getNativeType());
Assert::same($config['system'] === 'sqlsrv' ? 50 : 100, $column->getSize());
Assert::false($column->isNullable());
Assert::false($column->isAutoIncrement());
//Assert::null($column->getDefault());


$indexes = $table->getIndexes();
$index = reset($indexes);

if ($config['system'] !== 'sqlite') {
	Assert::same(2, count($indexes));
	Assert::true($index->isPrimary());
	Assert::true($index->isUnique());
	Assert::same(1, count($index->getColumns()));
	Assert::same('product_id', $index->getColumns()[0]->getName());

	$index = next($indexes);
}

Assert::same('title', $index->getName());
Assert::false($index->isPrimary());
Assert::false($index->isUnique());
Assert::same(1, count($index->getColumns()));
Assert::same('title', $index->getColumns()[0]->getName());
