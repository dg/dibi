<h1>dibi metatypes example</h1>
<pre>
<?php

require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


$res = dibi::query('SELECT * FROM [customers]');

// auto-convert this column to integer
$res->setType('customer_id', Dibi::FIELD_INTEGER);
$res->setType('added', Dibi::FIELD_DATETIME, 'H:i j.n.Y');

$row = $res->fetch();
var_dump($row);
