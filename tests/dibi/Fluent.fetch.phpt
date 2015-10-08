<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;
use Dibi\Row;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
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
Assert::equal([
	new Row(['product_id' => num(1), 'title' => 'Chair']),
	new Row(['product_id' => num(2), 'title' => 'Table']),
	new Row(['product_id' => num(3), 'title' => 'Computer']),
], $res->fetchAll());


// more complex association array
if ($config['system'] !== 'odbc') {
	$res = $conn->select(['products.title' => 'title', 'customers.name' => 'name'])->select('orders.amount')->as('amount')
		->from('products')
		->innerJoin('orders')->using('(product_id)')
		->innerJoin('customers')->using('([customer_id])')
		->orderBy('order_id');

	Assert::equal([
		'Arnold Rimmer' => [
			'Chair' => new Row(['title' => 'Chair', 'name' => 'Arnold Rimmer', 'amount' => num(7.0)]),
			'Computer' => new Row(['title' => 'Computer', 'name' => 'Arnold Rimmer', 'amount' => num(2.0)]),
		],
		'Dave Lister' => [
			'Table' => new Row(['title' => 'Table', 'name' => 'Dave Lister', 'amount' => num(3.0)]),
		],
		'Kristine Kochanski' => [
			'Computer' => new Row(['title' => 'Computer', 'name' => 'Kristine Kochanski', 'amount' => num(5.0)]),
		],
	], $res->fetchAssoc('name,title'));
}
