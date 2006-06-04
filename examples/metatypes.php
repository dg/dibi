<pre>
<?php

require_once '../dibi/dibi.php';


// mysql
dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => '***',
    'database' => 'test',
    'charset'  => 'utf8',
));

$res = dibi::query('SELECT * FROM [nucleus_item] WHERE [inumber] <> %i', 38);


$res = dibi::query('SELECT * FROM [nucleus_item] WHERE [inumber] <> %i', 38);

// auto-convert this field to integer
$res->setType('inumber', DibiResult::FIELD_INTEGER);
$record = $res->fetch();
var_dump($record);


// auto-detect all types
$res->setType(TRUE);
$record = $res->fetch();
var_dump($record);


?>
