<h1>dibi fetch example</h1>
<pre>
<?php

require_once '../dibi/dibi.php';


dibi::connect(array(
    'driver'   => 'sqlite',
    'database' => 'sample.sdb',
));


/*
TABLE products

product_id | title
-----------+----------
    1      | Chair
    2      | Table
    3      | Computer

*/


// fetch a single value
$res = dibi::query('SELECT [title] FROM [products]');
if (!$res) die('SQL error');

$value = $res->fetchSingle();
print_r($value); // Chair
echo '<hr>';


// fetch complete result set
$res = dibi::query('SELECT * FROM [products]');
$all = $res->fetchAll();
print_r($all);
echo '<hr>';


// fetch complete result set like association array
$assoc = $res->fetchAssoc('title'); // key
print_r($assoc);
echo '<hr>';


// fetch complete result set like pairs key => value
$pairs = $res->fetchPairs('product_id', 'title');
print_r($pairs);
echo '<hr>';


// fetch row by row
foreach ($res as $row => $fields) {
    print_r($fields);
}
echo '<hr>';


// fetch row by row with defined offset and limit
foreach ($res->getIterator(2, 1) as $row => $fields) {
    print_r($fields);
}


// more complex association array
$res = dibi::query('
SELECT * FROM [products]
INNER JOIN [orders] USING ([product_id])
INNER JOIN [customers] USING ([customer_id])
');

$assoc = $res->fetchAssoc('customers.name,products.title'); // key
print_r($assoc);
echo '<hr>';
