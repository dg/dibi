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


$res = dibi::query('SELECT * FROM [mytable] WHERE [inumber] <> %i', 38);
if (!$res) die('SQL error');


// auto-convert this field to integer
$res->setType('inumber', Dibi::FIELD_INTEGER);
$record = $res->fetch();
var_dump($record);


// auto-detect all types
$res->setType(TRUE);
$record = $res->fetch();
var_dump($record);
