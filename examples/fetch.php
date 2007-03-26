<pre>
<?php

require_once '../dibi/dibi.php';


// CHANGE TO REAL PARAMETERS!
dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'xxx',
    'database' => 'dibi',
    'charset'  => 'utf8',
));


$res = dibi::query('SELECT * FROM [mytable]');
if (!$res) die('SQL error');


// fetch a single value
$value = $res->fetchSingle();

// fetch complete result set
$all = $res->fetchAll();

// fetch complete result set like association array
$assoc = $res->fetchAssoc('id');

$assoc = $res->fetchAssoc('id', 'id2');

// fetch complete result set like pairs key => value
$pairs = $res->fetchPairs('id', 'name');


// fetch row by row
foreach ($res as $row => $fields) {
    print_r($fields);
}

// fetch row by row with defined offset and limit
foreach ($res->getIterator(2, 3) as $row => $fields) {
    print_r($fields);
}
