<pre>
<?php

require_once '../dibi/dibi.php';


// mysql
dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'xxx',  // change to real password!
    'database' => 'test',
    'charset'  => 'utf8',
));


$res = dibi::query('SELECT * FROM [nucleus_item] WHERE [inumber] <> %i', 38);
if (is_error($res))
    die('SQL error');


// auto-convert this field to integer
$res->setType('inumber', Dibi::FIELD_INTEGER);
$record = $res->fetch();
var_dump($record);


// auto-detect all types
$res->setType(TRUE);
$record = $res->fetch();
var_dump($record);


?>
