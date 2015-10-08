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
