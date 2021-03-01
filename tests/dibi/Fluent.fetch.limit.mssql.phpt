<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class MockDriver extends Dibi\Drivers\SqlsrvDriver
{
	public function __construct()
	{
	}


	public function connect(array &$config): void
	{
	}


	public function query(string $sql): ?Dibi\ResultDriver
	{
		return new MockResult;
	}
}


class MockResult extends Dibi\Drivers\SqlsrvResult
{
	public function __construct()
	{
	}


	public function getResultColumns(): array
	{
		return [];
	}


	public function fetch(bool $type): ?array
	{
		return null;
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
	reformat('SELECT TOP (1) * FROM (SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	(string) $fluent,
);


$fluent->fetch();
Assert::same(
	'SELECT TOP (1) * FROM (SELECT * FROM [customers] ORDER BY [customer_id]) t',
	dibi::$sql,
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT TOP (1) * FROM (SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql,
);
$fluent->fetchAll(0, 3);
Assert::same(
	reformat('SELECT TOP (3) * FROM (SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql,
);
Assert::same(
	reformat('SELECT TOP (1) * FROM (SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	(string) $fluent,
);


$fluent->limit(0);
$fluent->fetch();
Assert::same(
	reformat('SELECT TOP (0) * FROM (SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql,
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT TOP (0) * FROM (SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql,
);
Assert::same(
	reformat('SELECT TOP (0) * FROM (SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	(string) $fluent,
);


$fluent->removeClause('limit');
$fluent->removeClause('offset');
$fluent->fetch();
Assert::same(
	reformat('SELECT TOP (1) * FROM (SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql,
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT TOP (1) * FROM (SELECT * FROM [customers] ORDER BY [customer_id]) t'),
	dibi::$sql,
);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id]'),
	(string) $fluent,
);
