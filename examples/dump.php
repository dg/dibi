<?php

require_once '../dibi/dibi.php';

if (function_exists('date_default_timezone_set'))
     date_default_timezone_set('Europe/Prague');


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

// get last SQL
$sql = dibi::$sql;


// dump it
echo '<h1>dibi::dump()</h1>';

dibi::dump($sql);


// dump result table
echo '<h1>dibi::dumpResult()</h1>';

dibi::dumpResult($res);
