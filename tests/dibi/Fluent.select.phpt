<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new Dibi\Connection($config);


$max = 10;
$min = 5;

$fluent = $conn->select('*')
	->select('a')
	->select('b')->as('bAlias')
	->select(['c', 'd', 'e'])
	->select('%n', 'd');

Assert::same(
	reformat('SELECT * , [a] , [b] AS [bAlias] , [c], [d], [e] , [d]'),
	(string) $fluent,
);

$fluent->from('table')->as('table.Alias')
	->innerJoin('table1')->on('table.col = table1.col')
	->innerJoin('table2')->on('table.col = table2.col');

Assert::same(
	reformat('SELECT * , [a] , [b] AS [bAlias] , [c], [d], [e] , [d] FROM [table] AS [table.Alias] INNER JOIN [table1] ON table.col = table1.col INNER JOIN [table2] ON table.col = table2.col'),
	(string) $fluent,
);

$fluent->from('anotherTable');

Assert::same(
	reformat('SELECT * , [a] , [b] AS [bAlias] , [c], [d], [e] , [d] FROM [table] AS [table.Alias] INNER JOIN [table1] ON table.col = table1.col INNER JOIN [table2] ON table.col = table2.col , [anotherTable]'),
	(string) $fluent,
);

$fluent->removeClause('from')->from('anotherTable');

Assert::same(
	reformat('SELECT * , [a] , [b] AS [bAlias] , [c], [d], [e] , [d] FROM [anotherTable]'),
	(string) $fluent,
);

$fluent->as('anotherAlias')
	->clause('from')
		->innerJoin('table3')
		->on('table.col = table3.col');

Assert::same(
	reformat('SELECT * , [a] , [b] AS [bAlias] , [c], [d], [e] , [d] FROM [anotherTable] AS [anotherAlias] INNER JOIN [table3] ON table.col = table3.col'),
	(string) $fluent,
);

$fluent->where('col > %i', $max)
	->or('col < %i', $min)
	->where('active = 1')
	->where('col')->in([1, 2, 3])
	->orderBy('val')->asc()
	->orderBy('[val2] DESC')
	->orderBy(['val3' => -1]);

Assert::same(
	reformat('SELECT * , [a] , [b] AS [bAlias] , [c], [d], [e] , [d] FROM [anotherTable] AS [anotherAlias] INNER JOIN [table3] ON table.col = table3.col WHERE col > 10 OR col < 5 AND active = 1 AND [col] IN (1, 2, 3) ORDER BY [val] ASC , [val2] DESC , [val3] DESC'),
	(string) $fluent,
);

$fluent->orderBy(Dibi\Fluent::REMOVE);

Assert::same(
	reformat('SELECT * , [a] , [b] AS [bAlias] , [c], [d], [e] , [d] FROM [anotherTable] AS [anotherAlias] INNER JOIN [table3] ON table.col = table3.col WHERE col > 10 OR col < 5 AND active = 1 AND [col] IN (1, 2, 3)'),
	(string) $fluent,
);


$fluent = $conn->select('*')
	->select(
		$conn->select('count(*)')
		->from('precteni')->as('P')
		->where('P.id_clanku', '=', 'C.id_clanku'),
	)
	->from('clanky')->as('C')
	->where('id_clanku=%i', 123)
	->limit(1)
	->offset(0);

Assert::same(
	reformat([
		'odbc' => 'SELECT TOP 1 * FROM (SELECT * , (SELECT count(*) FROM [precteni] AS [P] WHERE P.id_clanku = C.id_clanku) FROM [clanky] AS [C] WHERE id_clanku=123) t',
		'sqlsrv' => 'SELECT * , (SELECT count(*) FROM [precteni] AS [P] WHERE P.id_clanku = C.id_clanku) FROM [clanky] AS [C] WHERE id_clanku=123 OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY',
		'SELECT * , (SELECT count(*) FROM [precteni] AS [P] WHERE P.id_clanku = C.id_clanku) FROM [clanky] AS [C] WHERE id_clanku=123 LIMIT 1',
	]),
	(string) $fluent,
);


$fluent = $conn->select('*')
	->select(['x' => 'xAlias'])
	->from('products')
	->innerJoin('orders')->using('(product_id)')
	->innerJoin('customers')->using('([customer_id])')
	->innerJoin('items')->using('(%n)', ['customer_id', 'order_id']);

Assert::same(
	reformat('SELECT * , [x] AS [xAlias] FROM [products] INNER JOIN [orders] USING (product_id) INNER JOIN [customers] USING ([customer_id]) INNER JOIN [items] USING ([customer_id], [order_id])'),
	(string) $fluent,
);



$fluent = $conn->command()->select()
	->from('products')
	->select('*')
	->innerJoin('orders')->using('(product_id)');

Assert::same(
	reformat('SELECT * FROM [products] INNER JOIN [orders] USING (product_id)'),
	(string) $fluent,
);


$fluent = $conn->select('*')
	->from(['me' => 't'])
	->where('col > %i', $max)
	->where(['x' => 'a', 'b', 'c']);

Assert::same(
	reformat([
		'sqlsrv' => "SELECT * FROM [me] AS [t] WHERE col > 10 AND ([x] = N'a') AND (b) AND (c)",
		"SELECT * FROM [me] AS [t] WHERE col > 10 AND ([x] = 'a') AND (b) AND (c)",
	]),
	(string) $fluent,
);


if ($config['system'] === 'mysql') {
	$fluent = $conn->select('*')
		->limit(' 1; DROP TABLE users')
		->offset(' 1; DROP TABLE users');

	Assert::error(function () use ($fluent) {
		(string) $fluent;
	}, E_USER_ERROR, "Expected number, ' 1; DROP TABLE users' given.");
}


$fluent = $conn->select('*')->from('abc')
	->where('x IN (%SQL)', $conn->select('id')->from('xyz'));

Assert::same(
	reformat('SELECT * FROM [abc] WHERE x IN ((SELECT [id] FROM [xyz]))'),
	(string) $fluent,
);
