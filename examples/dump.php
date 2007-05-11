<?php

require_once '../dibi/dibi.php';

if (function_exists('date_default_timezone_set'))
     date_default_timezone_set('Europe/Prague');


dibi::connect(array(
    'driver'   => 'sqlite',
    'database' => 'sample.sdb',
));



$res = dibi::query('
SELECT * FROM [products]
INNER JOIN [orders] USING ([product_id])
INNER JOIN [customers] USING ([customer_id])
');

// get last SQL
$sql = dibi::$sql;


// dump it
echo '<h1>dibi::dump()</h1>';

dibi::dump($sql);


// dump result table
echo '<h1>dibi::dumpResult()</h1>';

dibi::dumpResult($res);
