<pre>
<?php

require_once '../dibi/dibi.php';

dibi::$debug = true;

// mysql
dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'xxx',  // change to real password!
    'database' => 'test',
    'charset'  => 'utf8',
));


$res = dibi::query('SELECT * FROM table');
if (is_error($res))
    die('SQL error');


// fetch a single value
$value = $res->fetchSingle();

// fetch complete result set
$all = $res->fetchAll();

// fetch complete result set like association array
$assoc = $res->fetchAll('id');

$assoc = $res->fetchAll('id', 'id2');

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



?>