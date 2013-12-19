<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Dumping SQL and Result Set | dibi</h1>

<?php

require __DIR__ . '/../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
));


$res = dibi::query('
	SELECT * FROM products
	INNER JOIN orders USING (product_id)
	INNER JOIN customers USING (customer_id)
');


echo '<h2>dibi::dump()</h2>';

// dump last query (dibi::$sql)
dibi::dump();


// dump result table
echo '<h2>DibiResult::dump()</h2>';

$res->dump();
