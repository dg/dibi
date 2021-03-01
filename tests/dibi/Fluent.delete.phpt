<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new Dibi\Connection($config);


$fluent = $conn->delete('table')->as('bAlias')
	->setFlag('IGNORE');

Assert::same(
	reformat('DELETE IGNORE FROM [table] AS [bAlias]'),
	(string) $fluent,
);

$fluent->removeClause('from')->from('anotherTable');

Assert::same(
	reformat('DELETE IGNORE FROM [anotherTable]'),
	(string) $fluent,
);

$fluent->using('thirdTable');

Assert::same(
	reformat('DELETE IGNORE FROM [anotherTable] USING [thirdTable]'),
	(string) $fluent,
);

$fluent->setFlag('IGNORE', false);

Assert::same(
	reformat('DELETE FROM [anotherTable] USING [thirdTable]'),
	(string) $fluent,
);

$fluent->limit(10);

Assert::same(
	reformat('DELETE FROM [anotherTable] USING [thirdTable] LIMIT 10'),
	(string) $fluent,
);
