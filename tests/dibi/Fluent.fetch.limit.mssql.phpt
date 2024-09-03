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
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY'),
	(string) $fluent,
);


$fluent->fetch();
Assert::same(
	'SELECT * FROM [customers] ORDER BY [customer_id] OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY',
	dibi::$sql,
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY'),
	dibi::$sql,
);
$fluent->fetchAll(0, 3);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] OFFSET 0 ROWS FETCH NEXT 3 ROWS ONLY'),
	dibi::$sql,
);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY'),
	(string) $fluent,
);


$fluent->limit(0);
$fluent->fetch();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] OFFSET 0 ROWS FETCH NEXT 0 ROWS ONLY'),
	dibi::$sql,
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] OFFSET 0 ROWS FETCH NEXT 0 ROWS ONLY'),
	dibi::$sql,
);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] OFFSET 0 ROWS FETCH NEXT 0 ROWS ONLY'),
	(string) $fluent,
);


$fluent->removeClause('limit');
$fluent->removeClause('offset');
$fluent->fetch();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY'),
	dibi::$sql,
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY'),
	dibi::$sql,
);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id]'),
	(string) $fluent,
);
