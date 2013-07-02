<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Dumping SQL and Result Set | dibi</h1>

<?php

require dirname(__FILE__) . '/Nette/Debugger.php';
require dirname(__FILE__) . '/../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
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
