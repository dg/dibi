<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Transactions | dibi</h1>

<?php

require dirname(__FILE__) . '/Nette/Debugger.php';
require dirname(__FILE__) . '/../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
));


echo "<h2>Before</h2>\n";
dibi::query('SELECT * FROM [products]')->dump();
// -> 3 rows


dibi::begin();
dibi::query('INSERT INTO [products]', array(
	'title' => 'Test product',
));

echo "<h2>After INSERT</h2>\n";
dibi::query('SELECT * FROM [products]')->dump();


dibi::rollback(); // or dibi::commit();

echo "<h2>After rollback</h2>\n";
dibi::query('SELECT * FROM [products]')->dump();
// -> 3 rows again
