<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

if ($config['system'] === 'odbc' || $config['driver'] === 'pdo') {
	Tester\Environment::skip('Not supported.');
}

$conn = new DibiConnection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


$meta = $conn->getDatabaseInfo();

Assert::same(3, count($meta->getTables()));

$names = $meta->getTableNames();
sort($names);
Assert::equal(array('customers', 'orders', 'products'), $names);

Assert::false($meta->hasTable('xxxx'));

$table = $meta->getTable('products');
Assert::same('products', $table->name);
Assert::false($table->isView());

Assert::same(2, count($table->getColumns()));
Assert::false($table->hasColumn('xxxx'));
Assert::true($table->hasColumn('product_id'));
Assert::true($table->hasColumn('Product_id'));
Assert::same('product_id', $table->getColumn('Product_id')->name);

$column = $table->getColumn('product_id');
Assert::same('product_id', $column->name);
Assert::same('products', $column->table->name);
Assert::same('i', $column->type);
Assert::type('string', $column->nativeType);
Assert::false($column->nullable);
Assert::true($column->autoIncrement);

$column = $table->getColumn('title');
Assert::same('title', $column->name);
Assert::same('products', $column->table->name);
Assert::same('s', $column->type);
Assert::type('string', $column->nativeType);
Assert::same(100, $column->size);
Assert::true($column->nullable);
Assert::false($column->autoIncrement);
//Assert::null($column->default);


$indexes = $table->getIndexes();
$index = reset($indexes);

if ($config['system'] !== 'sqlite') {
	Assert::same(2, count($indexes));
	Assert::true($index->primary);
	Assert::true($index->unique);
	Assert::same(1, count($index->getColumns()));
	Assert::same('product_id', $index->columns[0]->name);

	$index = next($indexes);
}

Assert::same('title', $index->name);
Assert::false($index->primary);
Assert::false($index->unique);
Assert::same(1, count($index->getColumns()));
Assert::same('title', $index->columns[0]->name);
