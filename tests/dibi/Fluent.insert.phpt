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

$fluent = $conn->insert('table', $arr)
	->setFlag('IGNORE')->setFlag('DELAYED');

Assert::same(
	reformat('INSERT IGNORE DELAYED INTO [table] ([title], [price], [brand]) VALUES (\'Super Product\', 12, NULL)'),
	(string) $fluent,
);

$fluent->setFlag('IGNORE', false);

Assert::same(
	reformat('INSERT DELAYED INTO [table] ([title], [price], [brand]) VALUES (\'Super Product\', 12, NULL)'),
	(string) $fluent,
);

$fluent->setFlag('HIGH_priority');

Assert::same(
	reformat('INSERT DELAYED HIGH_PRIORITY INTO [table] ([title], [price], [brand]) VALUES (\'Super Product\', 12, NULL)'),
	(string) $fluent,
);

$fluent->into('anotherTable');

Assert::same(
	reformat('INSERT DELAYED HIGH_PRIORITY INTO [anotherTable] VALUES (\'Super Product\', 12, NULL)'),
	(string) $fluent,
);

$fluent->values('%l', $arr);

Assert::same(
	reformat('INSERT DELAYED HIGH_PRIORITY INTO [anotherTable] VALUES (\'Super Product\', 12, NULL) , (\'Super Product\', 12, NULL)'),
	(string) $fluent,
);

$fluent->values($arr);

Assert::same(
	reformat('INSERT DELAYED HIGH_PRIORITY INTO [anotherTable] VALUES (\'Super Product\', 12, NULL) , (\'Super Product\', 12, NULL) , (\'Super Product\', 12, NULL)'),
	(string) $fluent,
);
