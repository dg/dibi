<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Dibi\DateTime;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config + ['formatDateTime' => "'Y-m-d H:i:s.u'", 'formatDate' => "'Y-m-d'"]);


// Dibi detects INSERT or REPLACE command & booleans
Assert::same(
	reformat([
		'sqlsrv' => "REPLACE INTO [products] ([title], [price]) VALUES (N'Drticka', 318)",
		"REPLACE INTO [products] ([title], [price]) VALUES ('Drticka', 318)",
	]),
	$conn->translate('REPLACE INTO [products]', [
		'title' => 'Drticka',
		'price' => 318,
	]),
);


// multiple INSERT command
$array = [
	'title' => 'Super Product',
	'price' => 12,
	'brand' => null,
];
Assert::same(
	reformat([
		'sqlsrv' => "INSERT INTO [products] ([title], [price], [brand]) VALUES (N'Super Product', 12, NULL) , (N'Super Product', 12, NULL) , (N'Super Product', 12, NULL)",
		"INSERT INTO [products] ([title], [price], [brand]) VALUES ('Super Product', 12, NULL) , ('Super Product', 12, NULL) , ('Super Product', 12, NULL)",
	]),
	$conn->translate('INSERT INTO [products]', $array, $array, $array),
);


// multiple INSERT command II
$array = [
	['pole' => 'hodnota1', 'bit' => 1],
	['pole' => 'hodnota2', 'bit' => 1],
	['pole' => 'hodnota3', 'bit' => 1],
];
Assert::same(
	reformat([
		'sqlsrv' => "INSERT INTO [products]  ([pole], [bit]) VALUES (N'hodnota1', 1) , (N'hodnota2', 1) , (N'hodnota3', 1)",
		"INSERT INTO [products]  ([pole], [bit]) VALUES ('hodnota1', 1) , ('hodnota2', 1) , ('hodnota3', 1)",
	]),
	$conn->translate('INSERT INTO [products] %ex', $array),
);


// Dibi detects UPDATE command
Assert::same(
	reformat([
		'sqlsrv' => "UPDATE [colors] SET [color]=N'blue', [order]=12 WHERE [id]=123",
		"UPDATE [colors] SET [color]='blue', [order]=12 WHERE [id]=123",
	]),
	$conn->translate('UPDATE [colors] SET', [
		'color' => 'blue',
		'order' => 12,
	], 'WHERE [id]=%i', 123),
);


// IN array
$array = [1, 2, 3];
Assert::same(
	reformat('SELECT * FROM [people] WHERE [id] IN ( 1, 2, 3 )'),
	$conn->translate('SELECT * FROM [people] WHERE [id] IN (', $array, ')'),
);


// long numbers
Assert::same(
	reformat('SELECT -123456789123456789123456789'),
	$conn->translate('SELECT %i', '-123456789123456789123456789'),
);

// long float numbers
Assert::same(
	reformat('SELECT -.12345678912345678912345678e10'),
	$conn->translate('SELECT %f', '-.12345678912345678912345678e10'),
);

// invalid input
$e = Assert::exception(function () use ($conn) {
	$conn->translate('SELECT %s', (object) [123], ', %m', 123);
}, Dibi\Exception::class, 'SQL translate error: Invalid combination of type stdClass and modifier %s');
Assert::same('SELECT **Invalid combination of type stdClass and modifier %s** , **Unknown or unexpected modifier %m**', $e->getSql());

Assert::same(
	reformat([
		'sqlsrv' => "SELECT * FROM [table] WHERE id=10 AND name=N'ahoj'",
		"SELECT * FROM [table] WHERE id=10 AND name='ahoj'",
	]),
	$conn->translate('SELECT * FROM [table] WHERE id=%i AND name=%s', 10, 'ahoj'),
);

Assert::same(
	reformat([
		'sqlsrv' => "TEST ([cond] > 2) OR ([cond2] = N'3') OR (cond3 < RAND())",
		"TEST ([cond] > 2) OR ([cond2] = '3') OR (cond3 < RAND())",
	]),
	$conn->translate('TEST %or', ['[cond] > 2', '[cond2] = "3"', 'cond3 < RAND()']),
);

Assert::same(
	reformat([
		'sqlsrv' => "TEST ([cond] > 2) AND ([cond2] = N'3') AND (cond3 < RAND())",
		"TEST ([cond] > 2) AND ([cond2] = '3') AND (cond3 < RAND())",
	]),
	$conn->translate('TEST %and', ['[cond] > 2', '[cond2] = "3"', 'cond3 < RAND()']),
);


$where = [];
$where[] = '[age] > 20';
$where[] = '[email] IS NOT NULL';
Assert::same(
	reformat('SELECT * FROM [table] WHERE ([age] > 20) AND ([email] IS NOT NULL)'),
	$conn->translate('SELECT * FROM [table] WHERE %and', $where),
);


$where = [];
$where['age'] = null;
$where['email'] = 'ahoj';
$where['id%l'] = [10, 20, 30];
Assert::same(
	reformat([
		'sqlsrv' => "SELECT * FROM [table] WHERE ([age] IS NULL) AND ([email] = N'ahoj') AND ([id] IN (10, 20, 30))",
		"SELECT * FROM [table] WHERE ([age] IS NULL) AND ([email] = 'ahoj') AND ([id] IN (10, 20, 30))",
	]),
	$conn->translate('SELECT * FROM [table] WHERE %and', $where),
);


$where = [];
Assert::same(
	reformat('SELECT * FROM [table] WHERE 1=1'),
	$conn->translate('SELECT * FROM [table] WHERE %and', $where),
);


// ORDER BY array
$order = [
	'field1' => 'asc',
	'field2' => 'desc',
	'field3' => 1,
	'field4' => -1,
	'field5' => true,
	'field6' => false,
];
Assert::same(
	reformat('SELECT * FROM [people] ORDER BY [field1] ASC, [field2] DESC, [field3] ASC, [field4] DESC, [field5] ASC, [field6] DESC'),
	$conn->translate('SELECT * FROM [people] ORDER BY %by', $order),
);


// with limit = 2
Assert::same(
	reformat([
		'odbc' => 'SELECT TOP 2 * FROM (SELECT * FROM [products]) t',
		'sqlsrv' => 'SELECT * FROM [products] OFFSET 0 ROWS FETCH NEXT 2 ROWS ONLY',
		'SELECT * FROM [products] LIMIT 2',
	]),
	$conn->translate('SELECT * FROM [products] %lmt', 2),
);

if ($config['system'] === 'odbc') {
	Assert::exception(function () use ($conn) {
		$conn->translate('SELECT * FROM [products] %lmt %ofs', 2, 1);
	}, Dibi\Exception::class);
} else {
	// with limit = 2, offset = 1
	Assert::same(
		reformat([
			'sqlsrv' => 'SELECT * FROM [products] OFFSET 1 ROWS FETCH NEXT 2 ROWS ONLY',
			'SELECT * FROM [products] LIMIT 2 OFFSET 1',
		]),
		$conn->translate('SELECT * FROM [products] %lmt %ofs', 2, 1),
	);

	// with offset = 50
	Assert::same(
		reformat([
			'mysql' => 'SELECT * FROM `products` LIMIT 18446744073709551615 OFFSET 50',
			'postgre' => 'SELECT * FROM "products" OFFSET 50',
			'sqlsrv' => 'SELECT * FROM [products] OFFSET 50 ROWS',
			'SELECT * FROM [products] LIMIT -1 OFFSET 50',
		]),
		$conn->translate('SELECT * FROM [products] %ofs', 50),
	);
}




Assert::same(
	reformat([
		'odbc' => 'INSERT INTO test ([a2], [a4], [b1], [b2], [b3], [b4], [b5], [b6], [b7], [b8], [b9], [c1]) VALUES (#09/26/1212 00:00:00.000000#, #12/31/1969 22:13:20.000000#, #09/26/1212#, #09/26/1212 00:00:00.000000#, #12/31/1969#, #12/31/1969 22:13:20.000000#, #09/26/1212 00:00:00.000000#, #09/26/1212#, #09/26/1212 00:00:00.000000#, NULL, NULL, #09/26/1212 16:51:34.012400#)',
		'sqlsrv' => "INSERT INTO test ([a2], [a4], [b1], [b2], [b3], [b4], [b5], [b6], [b7], [b8], [b9], [c1]) VALUES (CONVERT(DATETIME2(7), '1212-09-26 00:00:00.000000'), CONVERT(DATETIME2(7), '1969-12-31 22:13:20.000000'), '1212-09-26', CONVERT(DATETIME2(7), '1212-09-26 00:00:00.000000'), '1969-12-31', CONVERT(DATETIME2(7), '1969-12-31 22:13:20.000000'), CONVERT(DATETIME2(7), '1212-09-26 00:00:00.000000'), '1212-09-26', CONVERT(DATETIME2(7), '1212-09-26 00:00:00.000000'), NULL, NULL, CONVERT(DATETIME2(7), '1212-09-26 16:51:34.012400'))",
		"INSERT INTO test ([a2], [a4], [b1], [b2], [b3], [b4], [b5], [b6], [b7], [b8], [b9], [c1]) VALUES ('1212-09-26 00:00:00.000000', '1969-12-31 22:13:20.000000', '1212-09-26', '1212-09-26 00:00:00.000000', '1969-12-31', '1969-12-31 22:13:20.000000', '1212-09-26 00:00:00.000000', '1212-09-26', '1212-09-26 00:00:00.000000', NULL, NULL, '1212-09-26 16:51:34.012400')",
	]),
	$conn->translate('INSERT INTO test', [
		'a2' => new DateTime('1212-09-26'),
		'a4' => new DateTime(-10000),
		'b1%d' => '1212-09-26',
		'b2%t' => '1212-09-26',
		'b3%d' => -10000,
		'b4%t' => -10000,
		'b5' => new DateTime('1212-09-26'),
		'b6%d' => new DateTime('1212-09-26'),
		'b7%t' => new DateTime('1212-09-26'),
		'b8%d' => null,
		'b9%t' => null,
		'c1%t' => new DateTime('1212-09-26 16:51:34.0124'),
	]),
);

Assert::exception(function () use ($conn) {
	$conn->translate('SELECT %s', new DateTime('1212-09-26'));
}, Dibi\Exception::class, 'SQL translate error: Invalid combination of type Dibi\DateTime and modifier %s');




// like
$args = [
	'SELECT * FROM products WHERE (title LIKE %like~ AND title LIKE %~like) OR title LIKE %~like~',
	'C',
	'r',
	"a\n%_\\'\"",
];

if ($config['system'] === 'postgre') {
	$conn->query('SET escape_string_warning = off'); // do not log warnings

	$conn->query('SET standard_conforming_strings = off');
	Assert::same(
		"SELECT * FROM products WHERE (title LIKE 'C%' AND title LIKE '%r') OR title LIKE '%a\n\\\\%\\\\_\\\\\\\\''\"%'",
		$conn->translate($args[0], $args[1], $args[2], $args[3]),
	);

	$conn->query('SET standard_conforming_strings = on');
	Assert::same(
		"SELECT * FROM products WHERE (title LIKE 'C%' AND title LIKE '%r') OR title LIKE '%a\n\\%\\_\\\\''\"%'",
		$conn->translate($args[0], $args[1], $args[2], $args[3]),
	);
} elseif ($config['driver'] !== 'sqlite') { // sqlite2
	Assert::same(
		reformat([
			'sqlite' => "SELECT * FROM products WHERE (title LIKE 'C%' ESCAPE '\\' AND title LIKE '%r' ESCAPE '\\') OR title LIKE '%a\n\\%\\_\\\\''\"%' ESCAPE '\\'",
			'odbc' => "SELECT * FROM products WHERE (title LIKE 'C%' AND title LIKE '%r') OR title LIKE '%a\n[%][_]\\''\"%'",
			'sqlsrv' => "SELECT * FROM products WHERE (title LIKE 'C%' AND title LIKE '%r') OR title LIKE '%a\n[%][_]\\''\"%'",
			"SELECT * FROM products WHERE (title LIKE 'C%' AND title LIKE '%r') OR title LIKE '%a\\n\\%\\_\\\\\\\\\\'\"%'",
		]),
		$conn->translate($args[0], $args[1], $args[2], $args[3]),
	);
}


$e = Assert::exception(function () use ($conn) {
	$conn->translate("SELECT '");
}, Dibi\Exception::class, 'SQL translate error: Alone quote');
Assert::same('SELECT **Alone quote**', $e->getSql());

Assert::match(
	pattern: reformat([
		'mysql' => <<<'XX'
			SELECT DISTINCT HIGH_PRIORITY SQL_BUFFER_RESULT
			CONCAT(last_name, ', ', first_name) AS full_name
			GROUP BY `user`
			HAVING MAX(salary) > %i 123
			INTO OUTFILE '/tmp/result\'.txt'
			FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
			LINES TERMINATED BY '\\n'
			XX,
		'sqlsrv' => <<<'XX'
			SELECT DISTINCT HIGH_PRIORITY SQL_BUFFER_RESULT
			CONCAT(last_name, N', ', first_name) AS full_name
			GROUP BY [user]
			HAVING MAX(salary) > %i 123
			INTO OUTFILE N'/tmp/result''.txt'
			FIELDS TERMINATED BY N',' OPTIONALLY ENCLOSED BY N'"'
			LINES TERMINATED BY N'\n'
			XX,
		<<<'XX'
			SELECT DISTINCT HIGH_PRIORITY SQL_BUFFER_RESULT
			CONCAT(last_name, ', ', first_name) AS full_name
			GROUP BY [user]
			HAVING MAX(salary) > %i 123
			INTO OUTFILE '/tmp/result''.txt'
			FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
			LINES TERMINATED BY '\n'
			XX,
	]),
	actual: $conn->translate('%sql', 'SELECT DISTINCT HIGH_PRIORITY SQL_BUFFER_RESULT
CONCAT(last_name, ", ", first_name) AS full_name
GROUP BY [user]
HAVING MAX(salary) > %i', 123, "
INTO OUTFILE '/tmp/result''.txt'
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
LINES TERMINATED BY '\\n'
"),
);





$array1 = [1, 2, 3];
$array2 = ['one', 'two', 'three'];
$array3 = [
	'col1' => 'one',
	'col2' => 'two',
	'col3' => 'thr.ee',
];
$array4 = [
	'a' => 12,
	'b' => null,
	'c' => new DateTime('12.3.2007'),
	'd' => 'any string',
];

$array5 = ['RAND()', '[col1] > [col2]'];


Assert::match(
	pattern: reformat([
		'mysql' => <<<'XX'
			SELECT *
			FROM `db`.`table`
			WHERE (`test`.`a` LIKE '1995-03-01'
				OR `b1` IN ( 1, 2, 3 )
				OR `b2` IN ('1', '2', '3' )
				OR `b3` IN ( )
				OR `b4` IN ( 'one', 'two', 'three' )
				OR `b5` IN (`col1` AS `one`, `col2` AS `two`, `col3` AS `thr.ee` )
				OR `b6` IN ('one', 'two', 'thr.ee')
				OR `b7` IN (NULL)
				OR `b8` IN (RAND() `col1` > `col2` )
				OR `b9` IN (RAND(), [col1] > [col2] )
				OR `b10` IN (  )
				AND `c` = 'embedded \' string'
				OR `d`=10
				OR `e`=NULL
				OR `true`= 1
				OR `false`= 0
				OR `str_null`=NULL
				OR `str_not_null`='hello'
			LIMIT 10
			XX,
		'sqlsrv' => <<<'XX'
			SELECT *
			FROM [db].[table]
			WHERE ([test].[a] LIKE '1995-03-01'
				OR [b1] IN ( 1, 2, 3 )
				OR [b2] IN (N'1', N'2', N'3' )
				OR [b3] IN ( )
				OR [b4] IN ( N'one', N'two', N'three' )
				OR [b5] IN ([col1] AS [one], [col2] AS [two], [col3] AS [thr.ee] )
				OR [b6] IN (N'one', N'two', N'thr.ee')
				OR [b7] IN (NULL)
				OR [b8] IN (RAND() [col1] > [col2] )
				OR [b9] IN (RAND(), [col1] > [col2] )
				OR [b10] IN (  )
				AND [c] = N'embedded '' string'
				OR [d]=10
				OR [e]=NULL
				OR [true]= 1
				OR [false]= 0
				OR [str_null]=NULL
				OR [str_not_null]=N'hello'
			LIMIT 10
			XX,
		'postgre' => <<<'XX'
			SELECT *
			FROM "db"."table"
			WHERE ("test"."a" LIKE '1995-03-01'
				OR "b1" IN ( 1, 2, 3 )
				OR "b2" IN ('1', '2', '3' )
				OR "b3" IN ( )
				OR "b4" IN ( 'one', 'two', 'three' )
				OR "b5" IN ("col1" AS "one", "col2" AS "two", "col3" AS "thr.ee" )
				OR "b6" IN ('one', 'two', 'thr.ee')
				OR "b7" IN (NULL)
				OR "b8" IN (RAND() "col1" > "col2" )
				OR "b9" IN (RAND(), [col1] > [col2] )
				OR "b10" IN (  )
				AND "c" = 'embedded '' string'
				OR "d"=10
				OR "e"=NULL
				OR "true"= TRUE
				OR "false"= FALSE
				OR "str_null"=NULL
				OR "str_not_null"='hello'
			LIMIT 10
			XX,
		'odbc' => <<<'XX'
			SELECT *
			FROM [db].[table]
			WHERE ([test].[a] LIKE #03/01/1995#
				OR [b1] IN ( 1, 2, 3 )
				OR [b2] IN ('1', '2', '3' )
				OR [b3] IN ( )
				OR [b4] IN ( 'one', 'two', 'three' )
				OR [b5] IN ([col1] AS [one], [col2] AS [two], [col3] AS [thr.ee] )
				OR [b6] IN ('one', 'two', 'thr.ee')
				OR [b7] IN (NULL)
				OR [b8] IN (RAND() [col1] > [col2] )
				OR [b9] IN (RAND(), [col1] > [col2] )
				OR [b10] IN (  )
				AND [c] = 'embedded '' string'
				OR [d]=10
				OR [e]=NULL
				OR [true]= 1
				OR [false]= 0
				OR [str_null]=NULL
				OR [str_not_null]='hello'
			LIMIT 10
			XX,
		<<<'XX'
			SELECT *
			FROM [db].[table]
			WHERE ([test].[a] LIKE '1995-03-01'
				OR [b1] IN ( 1, 2, 3 )
				OR [b2] IN ('1', '2', '3' )
				OR [b3] IN ( )
				OR [b4] IN ( 'one', 'two', 'three' )
				OR [b5] IN ([col1] AS [one], [col2] AS [two], [col3] AS [thr.ee] )
				OR [b6] IN ('one', 'two', 'thr.ee')
				OR [b7] IN (NULL)
				OR [b8] IN (RAND() [col1] > [col2] )
				OR [b9] IN (RAND(), [col1] > [col2] )
				OR [b10] IN (  )
				AND [c] = 'embedded '' string'
				OR [d]=10
				OR [e]=NULL
				OR [true]= 1
				OR [false]= 0
				OR [str_null]=NULL
				OR [str_not_null]='hello'
			LIMIT 10
			XX,
	]),
	actual: $conn->translate('SELECT *
FROM [db.table]
WHERE ([test.a] LIKE %d', '1995-03-01', '
	OR [b1] IN (', $array1, ')
	OR [b2] IN (%s', $array1, ')
	OR [b3] IN (%s', [], ')
	OR [b4] IN (', $array2, ')
	OR [b5] IN (%n', $array3, ')
	OR [b6] IN %l', $array3, '
	OR [b7] IN %in', [], '
	OR [b8] IN (%sql', $array5, ')
	OR [b9] IN (%SQL', $array5, ')
	OR [b10] IN (', [], ")
	AND [c] = 'embedded '' string'
	OR [d]=%i", 10.3, '
	OR [e]=%i', null, '
	OR [true]=', true, '
	OR [false]=', false, '
	OR [str_null]=%sn', '', '
	OR [str_not_null]=%sn', 'hello', '
LIMIT 10'),
);


Assert::same(
	reformat([
		'sqlsrv' => "TEST  [cond] > 2 [cond2] = N'3' cond3 < RAND() 123",
		"TEST  [cond] > 2 [cond2] = '3' cond3 < RAND() 123",
	]),
	$conn->translate('TEST %ex', ['[cond] > 2', '[cond2] = "3"', 'cond3 < RAND()'], 123),
);


Assert::same(
	reformat('TEST ([cond] > 2) OR ([cond2] > 3) OR ([cond3] = 10 + 1)'),
	$conn->translate('TEST %or', ['`cond` > 2', ['[cond2] > %i', '3'], 'cond3%sql' => ['10 + 1']]),
);


Assert::same(
	reformat('TEST ([cond] = 2) OR ([cond3] = RAND())'),
	$conn->translate('TEST %or', ['cond' => 2, 'cond3%sql' => 'RAND()']),
);


Assert::same(
	reformat([
		'sqlsrv' => "TEST ([cond1] 3) OR ([cond2] RAND()) OR ([cond3] LIKE N'string')",
		"TEST ([cond1] 3) OR ([cond2] RAND()) OR ([cond3] LIKE 'string')",
	]),
	$conn->translate('TEST %or', ['cond1%ex' => 3, 'cond2%ex' => 'RAND()', 'cond3%ex' => ['LIKE %s', 'string']]),
);


Assert::same(
	reformat([
		'odbc' => 'SELECT TOP 10 * FROM (SELECT * FROM [test] WHERE [id] LIKE \'%d%t\') t',
		'sqlsrv' => 'SELECT * FROM [test] WHERE [id] LIKE N\'%d%t\' OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY',
		'SELECT * FROM [test] WHERE [id] LIKE \'%d%t\' LIMIT 10',
	]),
	$conn->translate("SELECT * FROM [test] WHERE %n LIKE '%d%t' %lmt", 'id', 10),
);


$where = [
	'tablename.column' => 1,
];
Assert::same(
	reformat('SELECT * FROM [tablename] WHERE ([tablename].[column] = 1)'),
	$conn->translate('SELECT * FROM [tablename] WHERE %and', $where),
);


Assert::same(
	reformat('SELECT FROM ...'),
	$conn->translate('SELECT FROM ... %lmt', null),
);

Assert::same(
	reformat([
		'sqlsrv' => "SELECT N'%i'",
		"SELECT '%i'",
	]),
	$conn->translate("SELECT '%i'"),
);

Assert::same(
	reformat([
		'sqlsrv' => "SELECT N'%i'",
		"SELECT '%i'",
	]),
	$conn->translate('SELECT "%i"'),
);


Assert::same(
	reformat([
		'sqlsrv' => "INSERT INTO [products] ([product_id], [title]) VALUES (1, SHA1(N'Test product')) , (1, SHA1(N'Test product'))",
		"INSERT INTO [products] ([product_id], [title]) VALUES (1, SHA1('Test product')) , (1, SHA1('Test product'))",
	]),
	$conn->translate('INSERT INTO [products]', [
		'product_id' => 1,
		'title' => new Dibi\Expression('SHA1(%s)', 'Test product'),
	], [
		'product_id' => 1,
		'title' => new Dibi\Expression('SHA1(%s)', 'Test product'),
	]),
);

Assert::same(
	reformat([
		'sqlsrv' => "UPDATE [products] [product_id]=1, [title]=SHA1(N'Test product')",
		"UPDATE [products] [product_id]=1, [title]=SHA1('Test product')",
	]),
	$conn->translate('UPDATE [products]', [
		'product_id' => 1,
		'title' => new Dibi\Expression('SHA1(%s)', 'Test product'),
	]),
);

Assert::same(
	reformat([
		'sqlsrv' => "UPDATE [products] [product_id]=1, [title]=SHA1(N'Test product')",
		"UPDATE [products] [product_id]=1, [title]=SHA1('Test product')",
	]),
	$conn->translate('UPDATE [products]', [
		'product_id' => 1,
		'title' => new Dibi\Expression('SHA1(%s)', 'Test product'),
	]),
);

Assert::same(
	reformat([
		'sqlsrv' => "SELECT * FROM [products] WHERE [product_id]=1, [title]=SHA1(N'Test product')",
		"SELECT * FROM [products] WHERE [product_id]=1, [title]=SHA1('Test product')",
	]),
	$conn->translate('SELECT * FROM [products] WHERE', [
		'product_id' => 1,
		'title' => new Dibi\Expression('SHA1(%s)', 'Test product'),
	]),
);


Assert::same(
	reformat('SELECT * FROM [table] WHERE (([left] = 1) OR ([top] = 2)) AND (number < 100)'),
	$conn->translate('SELECT * FROM `table` WHERE %and', [
		new Dibi\Expression('%or', [
			'left' => 1,
			'top' => 2,
		]),
		new Dibi\Expression('number < %i', 100),
	]),
);


$e = Assert::exception(function () use ($conn) {
	$array6 = [
		'id' => [1, 2, 3, 4],
		'text' => ['ahoj', 'jak', 'se', new Dibi\Expression('SUM(%i)', '5')],
		'num%i' => ['1', ''],
	];
	$conn->translate('INSERT INTO test %m', $array6);
}, Dibi\Exception::class, 'SQL translate error: Multi-insert array "num%i" is different');
Assert::same('INSERT INTO test **Multi-insert array "num%i" is different**', $e->getSql());

$array6 = [
	'id' => [1, 2, 3, 4],
	'text' => ['ahoj', 'jak', 'se', new Dibi\Expression('SUM(%i)', '5')],
	'num%i' => ['1', '-1', 10.3, 1],
];

Assert::same(
	reformat([
		'sqlsrv' => "INSERT INTO test ([id], [text], [num]) VALUES (1, N'ahoj', 1), (2, N'jak', -1), (3, N'se', 10), (4, SUM(5), 1)",
		"INSERT INTO test ([id], [text], [num]) VALUES (1, 'ahoj', 1), (2, 'jak', -1), (3, 'se', 10), (4, SUM(5), 1)",
	]),
	$conn->translate('INSERT INTO test %m', $array6),
);


$by = [
	['funkce(nazev_pole) ASC'],
	'jine_pole' => 'DESC',
];

Assert::same(
	reformat('SELECT * FROM table ORDER BY funkce(nazev_pole) ASC, [jine_pole] DESC'),
	$conn->translate('SELECT * FROM table ORDER BY %by', $by),
);

Assert::same(
	reformat('INSERT INTO [test].*'),
	$conn->translate('INSERT INTO [test.*]'),
);

Assert::exception(function () use ($conn) {
	$conn->translate('INSERT INTO %i', 'ahoj');
}, Dibi\Exception::class, "Expected number, 'ahoj' given.");

Assert::exception(function () use ($conn) {
	$conn->translate('INSERT INTO %f', 'ahoj');
}, Dibi\Exception::class, "Expected number, 'ahoj' given.");


Assert::same(
	reformat('SELECT * FROM table'),
	$conn->translate('SELECT', new Dibi\Literal('* FROM table')),
);

Assert::same(
	reformat('SELECT * FROM table'),
	$conn->translate('SELECT %SQL', new Dibi\Literal('* FROM table')),
);

Assert::same(
	reformat('SELECT * FROM table'),
	$conn->translate(new Dibi\Literal('SELECT * FROM table')),
);


Assert::same(
	reformat('SELECT [a].[b] AS [c.d]'),
	$conn->translate('SELECT %n AS %N', 'a.b', 'c.d'),
);


setlocale(LC_ALL, 'czech');

Assert::same(
	reformat([
		'sqlsrv' => "UPDATE [colors] SET [color]=N'blue', [price]=-12.4, [spec]=-9E-005, [spec2]=1000, [spec3]=10000, [spec4]=10000 WHERE [price]=123.5",
		"UPDATE [colors] SET [color]='blue', [price]=-12.4, [spec]=-9E-005, [spec2]=1000, [spec3]=10000, [spec4]=10000 WHERE [price]=123.5",
	]),
	$conn->translate('UPDATE [colors] SET', [
		'color' => 'blue',
		'price' => -12.4,
		'spec%f' => '-9E-005',
		'spec2%f' => 1000.00,
		'spec3%i' => 10000,
		'spec4' => 10000,
	], 'WHERE [price]=%f', 123.5),
);
