<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new DibiConnection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


$ds = $conn->dataSource('SELECT * FROM products');
Assert::match(
	reformat("
SELECT *
FROM (SELECT * FROM products) t"),
	(string) $ds
);


Assert::same(3, $ds->count());
Assert::same(3, $ds->getTotalCount());
Assert::same(
	reformat('SELECT COUNT(*) FROM (SELECT * FROM products) t'),
	dibi::$sql
);


$ds->select('title');
$ds->orderBy('title', dibi::DESC);
$ds->where('title like "%a%"');
Assert::match(
	reformat("
SELECT [title]
FROM (SELECT * FROM products) t
 WHERE (title like '%a%')
 ORDER BY [title] DESC
 "),
	(string) $ds
);


$ds->select('product_id');
$ds->orderBy('product_id', dibi::ASC);
$ds->where('product_id = %i', 1);
Assert::match(
	reformat("
SELECT [title], [product_id]
FROM (SELECT * FROM products) t
 WHERE (title like '%a%') AND (product_id = 1)
 ORDER BY [title] DESC, [product_id] ASC
 "),
	(string) $ds
);


$ds->select(array('product_id'));
$ds->orderBy(array('product_id' => dibi::ASC));
$ds->where(array('product_id = 1'));
Assert::match(
	reformat("
SELECT [product_id]
FROM (SELECT * FROM products) t
 WHERE (title like '%a%') AND (product_id = 1) AND (product_id = 1)
 ORDER BY [product_id] ASC
 "),
	(string) $ds
);


Assert::same(1, $ds->count());
Assert::same(3, $ds->getTotalCount());
Assert::match(reformat("SELECT COUNT(*) FROM (
SELECT [product_id]
FROM (SELECT * FROM products) t
 WHERE (title like '%a%') AND (product_id = 1) AND (product_id = 1)
 ORDER BY [product_id] ASC
 ) t"), dibi::$sql);
Assert::same(1, $ds->toDataSource()->count());


Assert::equal(array(
	new DibiRow(array(
		'product_id' => 1,
	)),
), iterator_to_array($ds));

Assert::match(
	reformat("
SELECT [product_id]
FROM (SELECT * FROM products) t
 WHERE (title like '%a%') AND (product_id = 1) AND (product_id = 1)
 ORDER BY [product_id] ASC
 "),
	dibi::$sql
);


$fluent = $ds->toFluent();
Assert::same(1, $fluent->count());
Assert::match(
	reformat("SELECT * FROM (
SELECT [product_id]
FROM (SELECT * FROM products) t
 WHERE (title like '%a%') AND (product_id = 1) AND (product_id = 1)
 ORDER BY [product_id] ASC
 ) t"),
	(string) $fluent
);


$ds = $conn->select('title')->from('products')->toDataSource();
Assert::match(
	reformat("
SELECT *
FROM (SELECT [title] FROM [products]) t"),
	(string) $ds
);

Assert::equal(new DibiRow(array(
	'product_id' => 1,
	'title' => 'Chair',
)), $conn->dataSource('SELECT * FROM products ORDER BY product_id')->fetch());

Assert::same(1, $conn->dataSource('SELECT * FROM products ORDER BY product_id')->fetchSingle());

Assert::same(
	array(1 => 'Chair', 'Table', 'Computer'),
	$conn->dataSource('SELECT * FROM products ORDER BY product_id')->fetchPairs()
);

Assert::equal(array(
	1 => new DibiRow(array(
		'product_id' => 1,
		'title' => 'Chair',
	)),
	new DibiRow(array(
		'product_id' => 2,
		'title' => 'Table',
	)),
	new DibiRow(array(
		'product_id' => 3,
		'title' => 'Computer',
	)),
), $conn->dataSource('SELECT * FROM products ORDER BY product_id')->fetchAssoc('product_id'));


$ds = new DibiDataSource('products', $conn);

Assert::match(
	reformat("
SELECT *
FROM [products]"),
	(string) $ds
);

Assert::same(3, $ds->count());
Assert::same(3, $ds->getTotalCount());
Assert::same(reformat('SELECT COUNT(*) FROM [products]'), dibi::$sql);
