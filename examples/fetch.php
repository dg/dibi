<h1>dibi fetch example</h1>
<pre>
<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


/*
TABLE products

product_id | title
-----------+----------
	1      | Chair
	2      | Table
	3      | Computer

*/


// fetch a single row
$row = dibi::fetch('SELECT title FROM [products]');
Debug::dump($row); // Chair
echo '<hr>';


// fetch a single value
$value = dibi::fetchSingle('SELECT [title] FROM [products]');
Debug::dump($value); // Chair
echo '<hr>';


// fetch complete result set
$all = dibi::fetchAll('SELECT * FROM [products]');
Debug::dump($all);
echo '<hr>';


// fetch complete result set like association array
$res = dibi::query('SELECT * FROM [products]');
$assoc = $res->fetchAssoc('title'); // key
Debug::dump($assoc);
echo '<hr>';


// fetch complete result set like pairs key => value
$pairs = $res->fetchPairs('product_id', 'title');
Debug::dump($pairs);
echo '<hr>';


// fetch row by row
foreach ($res as $n => $row) {
	Debug::dump($row);
}
echo '<hr>';


// fetch row by row with defined offset
foreach ($res->getIterator(2) as $n => $row) {
	Debug::dump($row);
}

// fetch row by row with defined offset and limit
foreach ($res->getIterator(2, 1) as $n => $row) {
	Debug::dump($row);
}


// more complex association array
$res = dibi::query('
SELECT *
FROM [products]
INNER JOIN [orders] USING ([product_id])
INNER JOIN [customers] USING ([customer_id])
');

$assoc = $res->fetchAssoc('customers.name|products.title'); // key
Debug::dump($assoc);
echo '<hr>';

$assoc = $res->fetchAssoc('customers.name[]products.title'); // key
Debug::dump($assoc);
echo '<hr>';

$assoc = $res->fetchAssoc('customers.name->products.title'); // key
Debug::dump($assoc);
echo '<hr>';
