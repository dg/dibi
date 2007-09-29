<h1>dibi metatypes example</h1>
<pre>
<?php

require_once '../dibi/dibi.php';


dibi::connect(array(
    'driver'   => 'sqlite',
    'database' => 'sample.sdb',
));


$res = dibi::query('SELECT * FROM [customers]');
if (!$res) die('SQL error');


// auto-convert this field to integer
$res->setType('customer_id', Dibi::FIELD_INTEGER);
$row = $res->fetch();
var_dump($row);


// auto-detect all types
// WARNING: THIS WILL NOT WORK WITH SQLITE
$res->setType(TRUE);
$row = $res->fetch();
var_dump($row);
