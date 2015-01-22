<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new DibiConnection($config + array('formatDateTime' => "'Y-m-d H:i:s'", 'formatDate' => "'Y-m-d'"));


// dibi detects INSERT or REPLACE command & booleans
Assert::same(
	reformat("REPLACE INTO [products] ([title], [price]) VALUES ('Drticka', 318)"),
	$conn->translate('REPLACE INTO [products]', array(
	'title'  => 'Drticka',
	'price'  => 318,
)));


// multiple INSERT command
$array = array(
	'title'   => 'Super Product',
	'price'   => 12,
	'brand'   => NULL,
);
Assert::same(
	reformat('INSERT INTO [products] ([title], [price], [brand]) VALUES (\'Super Product\', 12, NULL) , (\'Super Product\', 12, NULL) , (\'Super Product\', 12, NULL)'),
	$conn->translate("INSERT INTO [products]", $array, $array, $array)
);


// multiple INSERT command II
$array = array(
	array('pole' => 'hodnota1', 'bit'  => 1),
	array('pole' => 'hodnota2', 'bit'  => 1),
	array('pole' => 'hodnota3', 'bit'  => 1)
);
Assert::same(
	reformat('INSERT INTO [products]  ([pole], [bit]) VALUES (\'hodnota1\', 1) , (\'hodnota2\', 1) , (\'hodnota3\', 1)'),
	$conn->translate("INSERT INTO [products] %ex", $array)
);


// dibi detects UPDATE command
Assert::same(
	reformat("UPDATE [colors] SET [color]='blue', [order]=12 WHERE [id]=123"),
	$conn->translate('UPDATE [colors] SET', array(
	'color' => 'blue',
	'order' => 12,
), "WHERE [id]=%i", 123));


// IN array
$array = array(1, 2, 3);
Assert::same(
	reformat('SELECT * FROM [people] WHERE [id] IN ( 1, 2, 3 )'),
	$conn->translate("SELECT * FROM [people] WHERE [id] IN (", $array, ")")
);


// long numbers
Assert::same(
	reformat('SELECT -123456789123456789123456789'),
	$conn->translate("SELECT %i", '-123456789123456789123456789')
);

// long float numbers
Assert::same(
	reformat('SELECT -.12345678912345678912345678e10'),
	$conn->translate("SELECT %f", '-.12345678912345678912345678e10')
);

// hex numbers
Assert::same(
	reformat('SELECT 17'),
	$conn->translate("SELECT %i", '0x11')
);

// invalid input
$e = Assert::exception(function() use ($conn) {
	$conn->translate("SELECT %s", (object) array(123), ', %m', 123);
}, 'DibiException', 'SQL translate error');
Assert::same('SELECT **Unexpected type object** , **Unknown or invalid modifier %m**', $e->getSql());

Assert::same(
	reformat('SELECT * FROM [table] WHERE id=10 AND name=\'ahoj\''),
	$conn->translate('SELECT * FROM [table] WHERE id=%i AND name=%s', 10, 'ahoj')
);

Assert::same(
	reformat('TEST ([cond] > 2) OR ([cond2] = \'3\') OR (cond3 < RAND())'),
	$conn->translate('TEST %or', array('[cond] > 2', '[cond2] = "3"', 'cond3 < RAND()'))
);

Assert::same(
	reformat('TEST ([cond] > 2) AND ([cond2] = \'3\') AND (cond3 < RAND())'),
	$conn->translate('TEST %and', array('[cond] > 2', '[cond2] = "3"', 'cond3 < RAND()'))
);

//
$where = array();
$where[] = '[age] > 20';
$where[] = '[email] IS NOT NULL';
Assert::same(
	reformat('SELECT * FROM [table] WHERE ([age] > 20) AND ([email] IS NOT NULL)'),
	$conn->translate('SELECT * FROM [table] WHERE %and', $where)
);


$where = array();
$where['age'] = NULL;
$where['email'] = 'ahoj';
$where['id%l'] = array(10, 20, 30);
Assert::same(
	reformat('SELECT * FROM [table] WHERE ([age] IS NULL) AND ([email] = \'ahoj\') AND ([id] IN (10, 20, 30))'),
	$conn->translate('SELECT * FROM [table] WHERE %and', $where)
);


$where = array();
Assert::same(
	reformat('SELECT * FROM [table] WHERE 1=1'),
	$conn->translate('SELECT * FROM [table] WHERE %and', $where)
);


// ORDER BY array
$order = array(
	'field1' => 'asc',
	'field2' => 'desc',
	'field3' => 1,
	'field4' => -1,
	'field5' => TRUE,
	'field6' => FALSE,
);
Assert::same(
	reformat("SELECT * FROM [people] ORDER BY [field1] ASC, [field2] DESC, [field3] ASC, [field4] DESC, [field5] ASC, [field6] DESC"),
	$conn->translate("SELECT * FROM [people] ORDER BY %by", $order)
);


// with limit = 2
Assert::same(
	reformat(array(
		'odbc' => 'SELECT TOP 2 * FROM (SELECT * FROM [products] ) t',
		'SELECT * FROM [products]  LIMIT 2',
	)),
	$conn->translate('SELECT * FROM [products] %lmt', 2)
);

if ($config['system'] === 'odbc') {
	Assert::exception(function() use ($conn) {
		$conn->translate('SELECT * FROM [products] %lmt %ofs', 2, 1);
	}, 'DibiException');
} else {
	// with limit = 2, offset = 1
	Assert::same(
		reformat('SELECT * FROM [products]   LIMIT 2 OFFSET 1'),
		$conn->translate('SELECT * FROM [products] %lmt %ofs', 2, 1)
	);

	// with offset = 50
	Assert::same(
		reformat(array(
			'mysql' => 'SELECT * FROM `products`  LIMIT 18446744073709551615 OFFSET 50',
			'pgsql' => 'SELECT * FROM "products"  OFFSET 50',
			'SELECT * FROM [products]  LIMIT -1 OFFSET 50',
		)),
		$conn->translate('SELECT * FROM [products] %ofs', 50)
	);
}




Assert::same(
	reformat(array(
		'odbc' => 'INSERT INTO test ([a2], [a4], [b1], [b2], [b3], [b4], [b5], [b6], [b7], [b8], [b9]) VALUES (#09/26/1212 00:00:00#, #12/31/1969 22:13:20#, #09/26/1212#, #09/26/1212 00:00:00#, #12/31/1969#, #12/31/1969 22:13:20#, #09/26/1212 00:00:00#, #09/26/1212#, #09/26/1212 00:00:00#, NULL, NULL)',
		"INSERT INTO test ([a2], [a4], [b1], [b2], [b3], [b4], [b5], [b6], [b7], [b8], [b9]) VALUES ('1212-09-26 00:00:00', '1969-12-31 22:13:20', '1212-09-26', '1212-09-26 00:00:00', '1969-12-31', '1969-12-31 22:13:20', '1212-09-26 00:00:00', '1212-09-26', '1212-09-26 00:00:00', NULL, NULL)",
	)),
	$conn->translate("INSERT INTO test", array(
	'a2' => new DibiDateTime('1212-09-26'),
	'a4' => new DibiDateTime(-10000),
	'b1%d' => '1212-09-26',
	'b2%t' => '1212-09-26',
	'b3%d' => -10000,
	'b4%t' => -10000,
	'b5' => new DateTime('1212-09-26'),
	'b6%d' => new DateTime('1212-09-26'),
	'b7%t' => new DateTime('1212-09-26'),
	'b8%d' => NULL,
	'b9%t' => NULL,
)));



// like
$args = array(
	"SELECT * FROM products WHERE (title LIKE %like~ AND title LIKE %~like) OR title LIKE %~like~",
	'C',
	'r',
	"a\n%_\\'\""
);

if ($config['system'] === 'pgsql') {
	$conn->query('SET escape_string_warning = off'); // do not log warnings

	$conn->query('SET standard_conforming_strings = off');
	Assert::same(
		"SELECT * FROM products WHERE (title LIKE 'C%' AND title LIKE '%r') OR title LIKE '%a\n\\\\%\\\\_\\\\\\\\''\"%'",
		$conn->translate($args[0], $args[1], $args[2], $args[3])
	);

	$conn->query('SET standard_conforming_strings = on');
	Assert::same(
		"SELECT * FROM products WHERE (title LIKE 'C%' AND title LIKE '%r') OR title LIKE '%a\n\\%\\_\\\\''\"%'",
		$conn->translate($args[0], $args[1], $args[2], $args[3])
	);

} elseif ($config['driver'] !== 'sqlite') { // sqlite2
	Assert::same(
		reformat(array(
			'sqlite' => "SELECT * FROM products WHERE (title LIKE 'C%' ESCAPE '\\' AND title LIKE '%r' ESCAPE '\\') OR title LIKE '%a\n\\%\\_\\\\''\"%' ESCAPE '\\'",
			'odbc' => "SELECT * FROM products WHERE (title LIKE 'C%' AND title LIKE '%r') OR title LIKE '%a\n[%][_]\\''\"%'",
			"SELECT * FROM products WHERE (title LIKE 'C%' AND title LIKE '%r') OR title LIKE '%a\\n\\%\\_\\\\\\\\\'\"%'",
		)),
		$conn->translate($args[0], $args[1], $args[2], $args[3])
	);
}


$e = Assert::exception(function() use ($conn) {
	$conn->translate("SELECT '");
}, 'DibiException', 'SQL translate error');
Assert::same('SELECT **Alone quote**', $e->getSql());

Assert::match(
	reformat(array(
		'mysql' => "SELECT DISTINCT HIGH_PRIORITY SQL_BUFFER_RESULT
CONCAT(last_name, ', ', first_name) AS full_name
GROUP BY `user`
HAVING MAX(salary) > %i 123
INTO OUTFILE '/tmp/result\'.txt'
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\\\"'
LINES TERMINATED BY '\\\\n'
",
		"SELECT DISTINCT HIGH_PRIORITY SQL_BUFFER_RESULT
CONCAT(last_name, ', ', first_name) AS full_name
GROUP BY [user]
HAVING MAX(salary) > %i 123
INTO OUTFILE '/tmp/result''.txt'
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
LINES TERMINATED BY '\\n'
"
	)),
	$conn->translate('%sql', "SELECT DISTINCT HIGH_PRIORITY SQL_BUFFER_RESULT
CONCAT(last_name, \", \", first_name) AS full_name
GROUP BY [user]
HAVING MAX(salary) > %i", 123, "
INTO OUTFILE '/tmp/result''.txt'
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
LINES TERMINATED BY '\\n'
")
);





$array1 = array(1, 2, 3);
$array2 = array('one', 'two', 'three');
$array3 = array(
	'col1' => 'one',
	'col2' => 'two',
	'col3' => 'three',
);
$array4 = array(
	'a'   => 12,
	'b'   => NULL,
	'c'   => new DibiDateTime('12.3.2007'),
	'd'   => 'any string',
);

$array5 = array('RAND()', '[col1] > [col2]');


Assert::match(
	reformat(array(
		'mysql' => "SELECT *
FROM `db`.`table`
WHERE (`test`.`a` LIKE '1995-03-01'
	OR `b1` IN ( 1, 2, 3 )
	OR `b2` IN ('1', '2', '3' )
	OR `b3` IN ( )
	OR `b4` IN ( 'one', 'two', 'three' )
	OR `b5` IN (`col1` AS `one`, `col2` AS `two`, `col3` AS `three` )
	OR `b6` IN ('one', 'two', 'three')
	OR `b7` IN (NULL)
	OR `b8` IN (RAND() `col1` > `col2` )
	OR `b9` IN (  )
	AND `c` = 'embedded \' string'
	OR `d`=10
	OR `e`=NULL
	OR `true`= 1
	OR `false`= 0
	OR `str_null`=NULL
	OR `str_not_null`='hello'
LIMIT 10",
		'pgsql' => 'SELECT *
FROM "db"."table"
WHERE ("test"."a" LIKE \'1995-03-01\'
	OR "b1" IN ( 1, 2, 3 )
	OR "b2" IN (\'1\', \'2\', \'3\' )
	OR "b3" IN ( )
	OR "b4" IN ( \'one\', \'two\', \'three\' )
	OR "b5" IN ("col1" AS "one", "col2" AS "two", "col3" AS "three" )
	OR "b6" IN (\'one\', \'two\', \'three\')
	OR "b7" IN (NULL)
	OR "b8" IN (RAND() "col1" > "col2" )
	OR "b9" IN (  )
	AND "c" = \'embedded \'\' string\'
	OR "d"=10
	OR "e"=NULL
	OR "true"= TRUE
	OR "false"= FALSE
	OR "str_null"=NULL
	OR "str_not_null"=\'hello\'
LIMIT 10',
		'odbc' => "SELECT *
FROM [db].[table]
WHERE ([test].[a] LIKE #03/01/1995#
	OR [b1] IN ( 1, 2, 3 )
	OR [b2] IN ('1', '2', '3' )
	OR [b3] IN ( )
	OR [b4] IN ( 'one', 'two', 'three' )
	OR [b5] IN ([col1] AS [one], [col2] AS [two], [col3] AS [three] )
	OR [b6] IN ('one', 'two', 'three')
	OR [b7] IN (NULL)
	OR [b8] IN (RAND() [col1] > [col2] )
	OR [b9] IN (  )
	AND [c] = 'embedded '' string'
	OR [d]=10
	OR [e]=NULL
	OR [true]= 1
	OR [false]= 0
	OR [str_null]=NULL
	OR [str_not_null]='hello'
LIMIT 10",
		"SELECT *
FROM [db].[table]
WHERE ([test].[a] LIKE '1995-03-01'
	OR [b1] IN ( 1, 2, 3 )
	OR [b2] IN ('1', '2', '3' )
	OR [b3] IN ( )
	OR [b4] IN ( 'one', 'two', 'three' )
	OR [b5] IN ([col1] AS [one], [col2] AS [two], [col3] AS [three] )
	OR [b6] IN ('one', 'two', 'three')
	OR [b7] IN (NULL)
	OR [b8] IN (RAND() [col1] > [col2] )
	OR [b9] IN (  )
	AND [c] = 'embedded '' string'
	OR [d]=10
	OR [e]=NULL
	OR [true]= 1
	OR [false]= 0
	OR [str_null]=NULL
	OR [str_not_null]='hello'
LIMIT 10",
	)),

	$conn->translate("SELECT *
FROM [db.table]
WHERE ([test.a] LIKE %d", '1995-03-01', "
	OR [b1] IN (", $array1, ")
	OR [b2] IN (%s", $array1, ")
	OR [b3] IN (%s", array(), ")
	OR [b4] IN (", $array2, ")
	OR [b5] IN (%n", $array3, ")
	OR [b6] IN %l", $array3, "
	OR [b7] IN %in", array(), "
	OR [b8] IN (%sql", $array5, ")
	OR [b9] IN (", array(), ")
	AND [c] = 'embedded '' string'
	OR [d]=%i", 10.3, "
	OR [e]=%i", NULL, "
	OR [true]=", TRUE, "
	OR [false]=", FALSE, "
	OR [str_null]=%sn", '', "
	OR [str_not_null]=%sn", 'hello', "
LIMIT 10")
);


Assert::same(
	reformat('TEST  [cond] > 2 [cond2] = \'3\' cond3 < RAND() 123'),
	$conn->translate('TEST %ex', array('[cond] > 2', '[cond2] = "3"', 'cond3 < RAND()'), 123)
);


Assert::same(
	reformat('TEST ([cond] > 2) OR ([cond2] > 3) OR ([cond3] = 10 + 1)'),
	$conn->translate('TEST %or', array('`cond` > 2', array('[cond2] > %i', '3'), 'cond3%sql' => array('10 + 1')))
);


Assert::same(
	reformat('TEST ([cond] = 2) OR ([cond3] = RAND())'),
	$conn->translate('TEST %or', array('cond' => 2, 'cond3%sql' => 'RAND()'))
);


Assert::same(
	reformat('TEST ([cond1] 3) OR ([cond2] RAND()) OR ([cond3] LIKE \'string\')'),
	$conn->translate('TEST %or', array('cond1%ex' => 3, 'cond2%ex' => 'RAND()', 'cond3%ex' => array('LIKE %s', 'string')))
);


Assert::same(
	reformat(array(
		'odbc' => 'SELECT TOP 10 * FROM (SELECT * FROM [test] WHERE [id] LIKE \'%d%t\' ) t',
		'SELECT * FROM [test] WHERE [id] LIKE \'%d%t\'  LIMIT 10',
	)),
	$conn->translate("SELECT * FROM [test] WHERE %n LIKE '%d%t' %lmt", 'id', 10)
);


$where = array(
		'tablename.column' => 1,
);
Assert::same(
	reformat('SELECT * FROM [tablename] WHERE ([tablename].[column] = 1)'),
	$conn->translate('SELECT * FROM [tablename] WHERE %and', $where)
);


Assert::same(
	reformat('SELECT FROM ... '),
	$conn->translate('SELECT FROM ... %lmt', NULL)
);

Assert::same(
	reformat('SELECT \'%i\''),
	$conn->translate("SELECT '%i'")
);

Assert::same(
	reformat('SELECT \'%i\''),
	$conn->translate('SELECT "%i"')
);


Assert::same(
	reformat('INSERT INTO [products] ([product_id], [title]) VALUES (1, SHA1(\'Test product\')) , (1, SHA1(\'Test product\'))'),
	$conn->translate('INSERT INTO [products]', array(
	'product_id' => 1,
	'title' => array('SHA1(%s)', 'Test product'),
), array(
	'product_id' => 1,
	'title' => array('SHA1(%s)', 'Test product'),
))
);

Assert::same(
	reformat('UPDATE [products] [product_id]=1, [title]=SHA1(\'Test product\')'),
	$conn->translate('UPDATE [products]', array(
	'product_id' => 1,
	'title' => array('SHA1(%s)', 'Test product'),
))
);


$e = Assert::exception(function() use ($conn) {
	$array6 = array(
		'id' => array(1, 2, 3, 4),
		'text' => array('ahoj', 'jak', 'se', array('SUM(%i)', '5')),
		'num%i' => array('1', ''),
	);
	$conn->translate('INSERT INTO test %m', $array6);
}, 'DibiException', 'SQL translate error');
Assert::same('INSERT INTO test **Multi-insert array "num%i" is different.**', $e->getSql());

$array6 = array(
	'id' => array(1, 2, 3, 4),
	'text' => array('ahoj', 'jak', 'se', array('SUM(%i)', '5')),
	'num%i' => array('1', '', 10.3, 1),
);

Assert::same(
	reformat('INSERT INTO test ([id], [text], [num]) VALUES (1, \'ahoj\', 1), (2, \'jak\', 0), (3, \'se\', 10), (4, SUM(5), 1)'),
	$conn->translate('INSERT INTO test %m', $array6)
);


$by = array (
	array('funkce(nazev_pole) ASC'),
	'jine_pole' => 'DESC'
);

Assert::same(
	reformat('SELECT * FROM table ORDER BY funkce(nazev_pole) ASC, [jine_pole] DESC'),
	$conn->translate("SELECT * FROM table ORDER BY %by", $by)
);

Assert::same(
	reformat('INSERT INTO [test].*'),
	$conn->translate('INSERT INTO [test.*]')
);

Assert::same(
	reformat('INSERT INTO 0'),
	$conn->translate('INSERT INTO %f', 'ahoj')
);


setLocale(LC_ALL, 'czech');

Assert::same(
	reformat("UPDATE [colors] SET [color]='blue', [price]=-12.4, [spec]=-9E-005, [spec2]=1000, [spec3]=10000, [spec4]=10000 WHERE [price]=123.5"),

	$conn->translate("UPDATE [colors] SET", array(
	'color' => 'blue',
	'price' => -12.4,
	'spec%f' => '-9E-005',
	'spec2%f' => 1000.00,
	'spec3%i' => 10000,
	'spec4' => 10000,
), "WHERE [price]=%f", 123.5)
);
