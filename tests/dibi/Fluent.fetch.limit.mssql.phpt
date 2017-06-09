<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class MockDriver extends Dibi\Drivers\SqlsrvDriver
{
	function __construct()
	{}

	function connect(array &$config)
	{}

	function query($sql)
	{
		return $this;
	}

	function getResultColumns()
	{
		return [];
	}

	function fetch($assoc)
	{
		return FALSE;
	}
}

$config['driver'] = new MockDriver;
$conn = new Dibi\Connection($config);


// fetch & limit
$fluent = $conn->select('*')
	->from('customers')
	->limit(1)
	->orderBy('customer_id');

Assert::same(
	reformat('SELECT TOP (1) * FROM (  SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	(string) $fluent
);


$fluent->fetch();
Assert::same(
	'SELECT TOP (1) * FROM (  SELECT * FROM [customers] ORDER BY [customer_id]) t',
	dibi::$sql
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT TOP (1) * FROM (  SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql
);
$fluent->fetchAll(0, 3);
Assert::same(
	reformat('SELECT TOP (3) * FROM (    SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql
);
Assert::same(
	reformat('SELECT TOP (1) * FROM (  SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	(string) $fluent
);


$fluent->limit(0);
$fluent->fetch();
Assert::same(
	reformat('SELECT TOP (0) * FROM (  SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT TOP (0) * FROM (  SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql
);
Assert::same(
	reformat('SELECT TOP (0) * FROM (  SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	(string) $fluent
);


$fluent->removeClause('limit');
$fluent->removeClause('offset');
$fluent->fetch();
Assert::same(
	reformat('SELECT TOP (1) * FROM ( SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT TOP (1) * FROM ( SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql
);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id]'),
	(string) $fluent
);
