<h1>dibi apply limit/offset example</h1>
<pre>
<?php

require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


// no limit
$res = dibi::query('SELECT * FROM [products]');
foreach ($res as $n => $row) {
	print_r($row);
}

echo '<hr>';

// with limit = 2
$res = dibi::query('SELECT * FROM [products] %lmt', 2);
foreach ($res as $n => $row) {
	print_r($row);
}

echo '<hr>';

// with limit = 2, offset = 1
$res = dibi::query('SELECT * FROM [products] %lmt %ofs', 2, 1);
foreach ($res as $n => $row) {
	print_r($row);
}
