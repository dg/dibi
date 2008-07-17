<style>
pre.dibi { padding-bottom: 10px; }
</style>
<h1>dibi SQL builder example</h1>
<pre>
<?php

require_once '../dibi/dibi.php';

// required since PHP 5.1.0
date_default_timezone_set('Europe/Prague');


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


// dibi detects INSERT or REPLACE command
dibi::test('
REPLACE INTO [products]', array(
	'title'  => 'Drtièka na trávu',
	'price'  => 318,
	'active' => TRUE,
));


// multiple INSERT command
$array = array(
	'title'   => 'Super Product',
	'price'   => 12,
	'brand'   => NULL,
	'created' => dibi::datetime(),
);
dibi::test("INSERT INTO [products]", $array, $array, $array);


// dibi detects UPDATE command
dibi::test("
UPDATE [colors] SET", array(
	'color' => 'blue',
	'order' => 12,
), "
WHERE [id]=%i", 123);


// SELECT
$ipMask = '192.168.%';
$timestamp = mktime(0, 0, 0, 10, 13, 1997);

dibi::test('
SELECT COUNT(*) as [count]
FROM [comments]
WHERE [ip] LIKE %s', $ipMask, '
AND [date] > ', dibi::date($timestamp)
);


// IN array
$array = array(1, 2, 3);
dibi::test("
SELECT *
FROM [people]
WHERE [id] IN (", $array, ")
");
