<style>
pre.dibi { padding-bottom: 10px; }
</style>
<h1>dibi SQL builder example</h1>
<pre>
<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';

date_default_timezone_set('Europe/Prague');


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


// dibi detects INSERT or REPLACE command
dibi::test('
	REPLACE INTO [products]', array(
		'title'  => 'Super product',
		'price'  => 318,
		'active' => TRUE,
));
// -> REPLACE INTO [products] ([title], [price], [active]) VALUES ('Super product', 318, 1)



// multiple INSERT command
$array = array(
	'title'   => 'Super Product',
	'price'   => 12,
	'brand'   => NULL,
	'created' => new DateTime,
);
dibi::test("INSERT INTO [products]", $array, $array, $array);
// -> INSERT INTO [products] ([title], [price], [brand], [created]) VALUES ('Super Product', ...) , (...) , (...)



// dibi detects UPDATE command
dibi::test("
	UPDATE [colors] SET", array(
		'color' => 'blue',
		'order' => 12,
	), "
	WHERE [id]=%i", 123);
// -> UPDATE [colors] SET [color]='blue', [order]=12 WHERE [id]=123



// SELECT
$ipMask = '192.168.%';
$timestamp = mktime(0, 0, 0, 10, 13, 1997);

dibi::test('
	SELECT COUNT(*) as [count]
	FROM [comments]
	WHERE [ip] LIKE %s', $ipMask, '
	AND [date] > ', dibi::date($timestamp)
);
// -> SELECT COUNT(*) as [count] FROM [comments] WHERE [ip] LIKE '192.168.%' AND [date] > 876693600



// IN array
$array = array(1, 2, 3);
dibi::test("
	SELECT *
	FROM [people]
	WHERE [id] IN (", $array, ")
");
// -> SELECT * FROM [people] WHERE [id] IN ( 1, 2, 3 )



// ORDER BY array
$order = array(
	'field1' => 'asc',
	'field2' => 'desc',
);
dibi::test("
SELECT *
FROM [people]
ORDER BY %by", $order, "
");
// -> SELECT * FROM [people] ORDER BY [field1] ASC, [field2] DESC
