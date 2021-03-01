<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);


// if & end
Assert::same(
	reformat('
SELECT *
FROM [customers]
/* WHERE ... LIKE ... */'),
	$conn->translate(
		'
SELECT *
FROM [customers]
%if',
		isset($name),
		'WHERE [name] LIKE %s',
		'xxx',
		'%end',
	),
);


// if & else & end (last end is optional)
Assert::same(
	reformat('
SELECT *
FROM  [customers] /* ... */'),
	$conn->translate(
		'
SELECT *
FROM %if',
		true,
		'[customers] %else [products]',
	),
);


// if & else & (optional) end
Assert::match(
	reformat('
SELECT *
FROM [people]
WHERE [id] > 0
	/* AND ...=...
	*/  AND [bar]=1
'),
	$conn->translate('
SELECT *
FROM [people]
WHERE [id] > 0
	%if', false, 'AND [foo]=%i', 1, '
	%else %if', true, 'AND [bar]=%i', 1, '
'),
);


// nested condition
Assert::match(
	reformat([
		'sqlsrv' => "
SELECT *
FROM [customers]
WHERE
	 [name] LIKE N'xxx'
		/* AND ...=1 */
	/* 1 LIMIT 10 */",
		"
SELECT *
FROM [customers]
WHERE
	 [name] LIKE 'xxx'
		/* AND ...=1 */
	/* 1 LIMIT 10 */",
	]),
	$conn->translate(
		'
SELECT *
FROM [customers]
WHERE
	%if',
		true,
		'[name] LIKE %s',
		'xxx',
		'
		%if',
		false,
		'AND [admin]=1 %end
	%else 1 LIMIT 10 %end',
	),
);


// limit & offset
Assert::same(
	'SELECT * FROM foo /* (limit 3) (offset 5) */',
	$conn->translate(
		'SELECT * FROM foo',
		'%if',
		false,
		'%lmt',
		3,
		'%ofs',
		5,
		'%end',
	),
);
