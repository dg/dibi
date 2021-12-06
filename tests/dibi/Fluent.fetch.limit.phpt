<?php

/**
 * @dataProvider? ../databases.ini mysql
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


// fetch & limit
$fluent = $conn->select('*')
	->from('customers')
	->limit(1)
	->offset(3)
	->orderBy('customer_id');

Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 1 OFFSET 3'),
	(string) $fluent,
);


$fluent->fetch();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 1 OFFSET 3'),
	dibi::$sql,
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 1 OFFSET 3'),
	dibi::$sql,
);
$fluent->fetchAll(2, 3);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 3 OFFSET 2'),
	dibi::$sql,
);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 1 OFFSET 3'),
	(string) $fluent,
);


$fluent->limit(0);
$fluent->fetch();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 0 OFFSET 3'),
	dibi::$sql,
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 0 OFFSET 3'),
	dibi::$sql,
);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 0 OFFSET 3'),
	(string) $fluent,
);


$fluent->removeClause('limit');
$fluent->fetch();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 1 OFFSET 3'),
	dibi::$sql,
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 1 OFFSET 3'),
	dibi::$sql,
);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 18446744073709551615 OFFSET 3'),
	(string) $fluent,
);


$fluent->removeClause('offset');
$fluent->fetch();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 1'),
	dibi::$sql,
);
$fluent->fetchSingle();
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id] LIMIT 1'),
	dibi::$sql,
);
Assert::same(
	reformat('SELECT * FROM [customers] ORDER BY [customer_id]'),
	(string) $fluent,
);
