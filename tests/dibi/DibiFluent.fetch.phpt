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
$res = $conn->select('title')->from('products')->orderBy('product_id');
Assert::equal('Chair', $res->fetchSingle());


// fetch complete result set
$res = $conn->select('*')->from('products')->orderBy('product_id');
Assert::equal(array(
	new DibiRow(array('product_id' => num(1), 'title' => 'Chair')),
	new DibiRow(array('product_id' => num(2), 'title' => 'Table')),
	new DibiRow(array('product_id' => num(3), 'title' => 'Computer')),
), $res->fetchAll());


// more complex association array
if ($config['system'] !== 'odbc') {
	$res = $conn->select(array('products.title' => 'title', 'customers.name' => 'name'))->select('orders.amount')->as('amount')
		->from('products')
		->innerJoin('orders')->using('(product_id)')
		->innerJoin('customers')->using('([customer_id])')
		->orderBy('order_id');

	Assert::equal(array(
		'Arnold Rimmer' => array(
			'Chair' => new DibiRow(array('title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0))),
			'Computer' => new DibiRow(array('title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0))),
		),
		'Dave Lister' => array(
			'Table' => new DibiRow(array('title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0))),
		),
		'Kristine Kochanski' => array(
			'Computer' => new DibiRow(array('title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0))),
		),
	), $res->fetchAssoc('name,title'));
}

$fluent = $conn->select('*')
	->from('customers')
	->limit(1)
	->offset(3)
	->orderBy('customer_id');

Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 1 OFFSET 3'),
	(string) $fluent
);

Assert::equal(new DibiRow(array('customer_id' => num(4), 'name' => 'Holly')), $fluent->fetch());

$fluent->removeClause('limit')->limit(2);
Assert::equal(new DibiRow(array('customer_id' => num(4), 'name' => 'Holly')), $fluent->fetch());

$fluent->removeClause('limit')->limit('%i', '1');
Assert::equal(new DibiRow(array('customer_id' => num(4), 'name' => 'Holly')), $fluent->fetch());

$fluent->removeClause('limit');
Assert::equal(new DibiRow(array('customer_id' => num(4), 'name' => 'Holly')), $fluent->fetch());

$fluent->removeClause('select')->select('name')->limit(1);
Assert::equal('Holly', $fluent->fetchSingle());

$fluent = $conn->select('name')->from('customers')->limit(1)->offset(3)->limit(1);
Assert::equal(new DibiRow(array('name' => 'Holly')), $fluent->fetch());
