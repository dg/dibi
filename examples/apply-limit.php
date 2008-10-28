<h1>dibi apply limit/offset example</h1>
<pre>
<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


// no limit
dibi::test('SELECT * FROM [products]');
// -> SELECT * FROM [products]


echo '<hr>';

// with limit = 2
dibi::test('SELECT * FROM [products] %lmt', 2);
// -> SELECT * FROM [products] LIMIT 2


echo '<hr>';

// with limit = 2, offset = 1
dibi::test('SELECT * FROM [products] %lmt %ofs', 2, 1);
// -> SELECT * FROM [products] LIMIT 2 OFFSET 1
