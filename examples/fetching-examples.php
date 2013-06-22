<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Fetching Examples | dibi</h1>

<?php

require dirname(__FILE__) . '/Nette/Debugger.php';
require dirname(__FILE__) . '/../dibi/dibi.php';

ndebug();

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
echo "<h2>fetch()</h2>\n";
$row = dibi::fetch('SELECT title FROM products');
dump($row); // Chair


// fetch a single value
echo "<h2>fetchSingle()</h2>\n";
$value = dibi::fetchSingle('SELECT title FROM products');
dump($value); // Chair


// fetch complete result set
echo "<h2>fetchAll()</h2>\n";
$all = dibi::fetchAll('SELECT * FROM products');
dump($all);


// fetch complete result set like association array
echo "<h2>fetchAssoc('title')</h2>\n";
$res = dibi::query('SELECT * FROM products');
$assoc = $res->fetchAssoc('title'); // key
dump($assoc);


// fetch complete result set like pairs key => value
echo "<h2>fetchPairs('product_id', 'title')</h2>\n";
$pairs = $res->fetchPairs('product_id', 'title');
dump($pairs);


// fetch row by row
echo "<h2>using foreach</h2>\n";
foreach ($res as $n => $row) {
	dump($row);
}


// more complex association array
$res = dibi::query('
	SELECT *
	FROM products
	INNER JOIN orders USING (product_id)
	INNER JOIN customers USING (customer_id)
');

echo "<h2>fetchAssoc('customers.name|products.title')</h2>\n";
$assoc = $res->fetchAssoc('customers.name|products.title'); // key
dump($assoc);

echo "<h2>fetchAssoc('customers.name[]products.title')</h2>\n";
$assoc = $res->fetchAssoc('customers.name[]products.title'); // key
dump($assoc);

echo "<h2>fetchAssoc('customers.name->products.title')</h2>\n";
$assoc = $res->fetchAssoc('customers.name->products.title'); // key
dump($assoc);
