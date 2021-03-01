<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new Dibi\Connection($config);


$arr = [
	'title' => 'Super Product',
	'price' => 12,
	'brand' => null,
];

$fluent = $conn->update('table', $arr)
	->setFlag('IGNORE')->setFlag('DELAYED');

Assert::same(
	reformat('UPDATE IGNORE DELAYED [table] SET [title]=\'Super Product\', [price]=12, [brand]=NULL'),
	(string) $fluent,
);

$fluent->set(['another' => 123]);

Assert::same(
	reformat('UPDATE IGNORE DELAYED [table] SET [title]=\'Super Product\', [price]=12, [brand]=NULL , [another]=123'),
	(string) $fluent,
);


$arr = [
	'table1.title' => 'Super Product',
	'table2.price' => 12,
	'table2.brand' => null,
];
$fluent = $conn->update(['table1', 'table2'], $arr);
Assert::same(
	reformat('UPDATE [table1], [table2] SET [table1].[title]=\'Super Product\', [table2].[price]=12, [table2].[brand]=NULL'),
	(string) $fluent,
);
