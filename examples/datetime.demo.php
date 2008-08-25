<h1>IDibiVariable example</h1>
<?php

require_once '../dibi/dibi.php';


// required since PHP 5.1.0
date_default_timezone_set('Europe/Prague');



// CHANGE TO REAL PARAMETERS!
dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
	'formatDate' => "'Y-m-d'",
	'formatDateTime' => "'Y-m-d H-i-s'",
));



// generate and dump SQL
dibi::test("
INSERT INTO [mytable]", array(
	'id'    => 123,
	'date'  => dibi::date('12.3.2007'),
	'stamp' => dibi::dateTime('23.1.2007 10:23'),
));
