<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new DibiConnection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


function num($n)
{
	global $config;
	if (substr(@$config['dsn'], 0, 5) === 'odbc:' || $config['driver'] === 'sqlite') {
		$n = is_float($n) ? "$n.0" : (string) $n;
	}
	return $n;
}


// fetch a single value
$res = $conn->query('SELECT [title] FROM [products]');
Assert::same('Chair', $res->fetchSingle());


// fetch complete result set
$res = $conn->query('SELECT * FROM [products] ORDER BY product_id');
Assert::equal([
	new DibiRow(['product_id' => num(1), 'title' => 'Chair']),
	new DibiRow(['product_id' => num(2), 'title' => 'Table']),
	new DibiRow(['product_id' => num(3), 'title' => 'Computer']),
], $res->fetchAll());


// fetch complete result set like pairs key => value
$res = $conn->query('SELECT * FROM [products] ORDER BY product_id');
Assert::same(
	[1 => 'Chair', 'Table', 'Computer'],
	$res->fetchPairs('product_id', 'title')
);

$res = $conn->query('SELECT * FROM [products] ORDER BY product_id');
Assert::same(
	[1 => 'Chair', 'Table', 'Computer'],
	$res->fetchPairs()
);


// fetch row by row
$res = $conn->query('SELECT * FROM [products] ORDER BY product_id');
Assert::equal([
	new DibiRow(['product_id' => num(1), 'title' => 'Chair']),
	new DibiRow(['product_id' => num(2), 'title' => 'Table']),
	new DibiRow(['product_id' => num(3), 'title' => 'Computer']),
], iterator_to_array($res));


// fetch complete result set like association array
$res = $conn->query('SELECT * FROM [products] ORDER BY product_id');
Assert::equal([
	'Chair' => new DibiRow(['product_id' => num(1), 'title' => 'Chair']),
	'Table' => new DibiRow(['product_id' => num(2), 'title' => 'Table']),
	'Computer' => new DibiRow(['product_id' => num(3), 'title' => 'Computer']),
], $res->fetchAssoc('title'));



// more complex association array
function query($conn) {

	return $conn->query($conn->getConfig('system') === 'odbc' ? '
		SELECT products.title, customers.name, orders.amount
		FROM ([products]
		INNER JOIN [orders] ON [products.product_id] = [orders.product_id])
		INNER JOIN [customers] ON [orders.customer_id] = [customers.customer_id]
		ORDER BY orders.order_id
	' : '
		SELECT products.title AS title, customers.name AS name, orders.amount AS amount
		FROM [products]
		INNER JOIN [orders] USING ([product_id])
		INNER JOIN [customers] USING ([customer_id])
		ORDER BY orders.order_id
	');
}


Assert::equal([
	'Arnold Rimmer' => [
		'Chair' => new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
		'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
	],
	'Dave Lister' => [
		'Table' => new DibiRow(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
	],
	'Kristine Kochanski' => [
		'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
	],
], query($conn)->fetchAssoc('name,title'));


Assert::equal([
	'Arnold Rimmer' => [
		[
			'Chair' => new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
		],
		[
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
		],
	],
	'Dave Lister' => [
		[
			'Table' => new DibiRow(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
		],
	],
	'Kristine Kochanski' => [
		[
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
		],
	],
], query($conn)->fetchAssoc('name,#,title'));


Assert::equal([
	'Arnold Rimmer' => [
		'title' => [
			'Chair' => new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
		],
		'name' => 'Arnold Rimmer',
		'amount' => num(7.0),
	],
	'Dave Lister' => [
		'title' => [
			'Table' => new DibiRow(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
		],
		'name' => 'Dave Lister',
		'amount' => num(3.0),
	],
	'Kristine Kochanski' => [
		'title' => [
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
		],
		'name' => 'Kristine Kochanski',
		'amount' => num(5.0),
	],
], query($conn)->fetchAssoc('name,=,title'));


Assert::equal([
	'Arnold Rimmer' => new DibiRow([
		'title' => [
			'Chair' => new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
		],
		'name' => 'Arnold Rimmer',
		'amount' => num(7.0),
	]),
	'Dave Lister' => new DibiRow([
		'title' => [
			'Table' => new DibiRow(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
		],
		'name' => 'Dave Lister',
		'amount' => num(3.0),
	]),
	'Kristine Kochanski' => new DibiRow([
		'title' => [
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
		],
		'name' => 'Kristine Kochanski',
		'amount' => num(5.0),
	]),
], query($conn)->fetchAssoc('name,@,title'));


Assert::equal([
	new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
	new DibiRow([
		'title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
	new DibiRow([
		'title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
	new DibiRow([
		'title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
], query($conn)->fetchAssoc('@,='));


Assert::equal([
	'Arnold Rimmer' => [
		'title' => [
			'Chair' => new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
		],
		'name' => 'Arnold Rimmer',
		'amount' => num(7.0),
	],
	'Dave Lister' => [
		'title' => [
			'Table' => new DibiRow(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
		],
		'name' => 'Dave Lister',
		'amount' => num(3.0),
	],
	'Kristine Kochanski' => [
		'title' => [
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
		],
		'name' => 'Kristine Kochanski',
		'amount' => num(5.0),
	],
], query($conn)->fetchAssoc('name,=,title,@'));


// old syntax
Assert::equal([
	'Arnold Rimmer' => [
		'Chair' => new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
		'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
	],
	'Dave Lister' => [
		'Table' => new DibiRow(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
	],
	'Kristine Kochanski' => [
		'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
	],
], query($conn)->fetchAssoc('name|title'));


Assert::equal([
	'Arnold Rimmer' => [
		[
			'Chair' => new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
		],
		[
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
		],
	],
	'Dave Lister' => [
		[
			'Table' => new DibiRow(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
		],
	],
	'Kristine Kochanski' => [
		[
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
		],
	],
], query($conn)->fetchAssoc('name[]title'));


Assert::equal([
	'Arnold Rimmer' => new DibiRow([
		'title' => [
			'Chair' => new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
		],
		'name' => 'Arnold Rimmer',
		'amount' => num(7.0),
	]),
	'Dave Lister' => new DibiRow([
		'title' => [
			'Table' => new DibiRow(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
		],
		'name' => 'Dave Lister',
		'amount' => num(3.0),
	]),
	'Kristine Kochanski' => new DibiRow([
		'title' => [
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
		],
		'name' => 'Kristine Kochanski',
		'amount' => num(5.0),
	]),
], query($conn)->fetchAssoc('name->title'));


Assert::equal([
	'Arnold Rimmer' => new DibiRow([
		'title' => ['Chair' => 'Arnold Rimmer', 'Computer' => 'Arnold Rimmer'],
		'name' => 'Arnold Rimmer',
		'amount' => num(7.0),
	]),
	'Dave Lister' => new DibiRow([
		'title' => ['Table' => 'Dave Lister'],
		'name' => 'Dave Lister',
		'amount' => num(3.0),
	]),
	'Kristine Kochanski' => new DibiRow([
		'title' => ['Computer' => 'Kristine Kochanski'],
		'name' => 'Kristine Kochanski',
		'amount' => num(5.0),
	]),
], query($conn)->fetchAssoc('name->title=name'));


Assert::equal([
	new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
	new DibiRow(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
	new DibiRow(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
	new DibiRow(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
], query($conn)->fetchAssoc('[]'));


Assert::equal([
	'Arnold Rimmer' => new DibiRow([
		'title' => [
			'Chair' => new DibiRow(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
		],
		'name' => 'Arnold Rimmer',
		'amount' => num(7.0),
	]),
	'Dave Lister' => new DibiRow([
		'title' => [
			'Table' => new DibiRow(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
		],
		'name' => 'Dave Lister',
		'amount' => num(3.0),
	]),
	'Kristine Kochanski' => new DibiRow([
		'title' => [
			'Computer' => new DibiRow(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
		],
		'name' => 'Kristine Kochanski',
		'amount' => num(5.0),
	]),
], query($conn)->fetchAssoc('name->title->'));
