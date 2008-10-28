<h1>dibi transaction example</h1>
<pre>
<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


echo "<h2>Before:</h2>\n";
dibi::query('SELECT * FROM [products]')->dump();
// -> 3 rows


dibi::begin();
dibi::query('INSERT INTO [products]', array(
	'title' => 'Test product',
));
dibi::rollback(); // or dibi::commit();



echo "<h2>After:</h2>\n";
dibi::query('SELECT * FROM [products]')->dump();
// -> 3 rows
