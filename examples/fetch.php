<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>dibi fetch example</h1>

<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
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


// fetch a single value
$value = dibi::fetchSingle('SELECT [title] FROM [products]');
Debug::dump($value); // Chair


// fetch complete result set
$all = dibi::fetchAll('SELECT * FROM [products]');
Debug::dump($all);


// fetch complete result set like association array
$res = dibi::query('SELECT * FROM [products]');
$assoc = $res->fetchAssoc('title'); // key
Debug::dump($assoc);


// fetch complete result set like pairs key => value
$pairs = $res->fetchPairs('product_id', 'title');
Debug::dump($pairs);


// fetch row by row
foreach ($res as $n => $row) {
	Debug::dump($row);
}


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

$assoc = $res->fetchAssoc('customers.name[]products.title'); // key
Debug::dump($assoc);

$assoc = $res->fetchAssoc('customers.name->products.title'); // key
Debug::dump($assoc);
