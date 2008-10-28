<h1>dibi dump example</h1>
<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));



$res = dibi::query('
SELECT * FROM [products]
INNER JOIN [orders] USING ([product_id])
INNER JOIN [customers] USING ([customer_id])
');


echo '<h2>dibi::dump()</h2>';

// dump last query (dibi::$sql)
dibi::dump();
// -> SELECT * FROM [products] INNER JOIN [orders] USING ([product_id]) INNER JOIN [customers] USING ([customer_id])


// dump result table
echo '<h2>DibiResult::dump()</h2>';

$res->dump();
// -> [table]
