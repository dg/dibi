<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Result Set Data Types  | dibi</h1>

<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';

date_default_timezone_set('Europe/Prague');


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
));


// using manual hints
$res = dibi::query('SELECT * FROM [customers]');

$res->setType('customer_id', Dibi::INTEGER)
	->setType('added', Dibi::DATETIME, 'H:i j.n.Y');

Debug::dump( $res->fetch() );
// outputs:
// object(DibiRow)#3 (3) {
//     customer_id => int(1)
//     name =>  string(11) "Dave Lister"
//     added =>  object(DateTime53) {}
// }



// using auto-detection (works well with MySQL or other strictly typed databases)
$res = dibi::query('SELECT * FROM [customers]');

$res->detectTypes();

Debug::dump( $res->fetch() );
// outputs:
// object(DibiRow)#3 (3) {
//     customer_id => int(1)
//     name =>  string(11) "Dave Lister"
//     added =>  string(15) "17:20 11.3.2007"
// }
