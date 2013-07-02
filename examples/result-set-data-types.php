<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Result Set Data Types  | dibi</h1>

<?php

require dirname(__FILE__) . '/Nette/Debugger.php';
require dirname(__FILE__) . '/../dibi/dibi.php';

ndebug();
date_default_timezone_set('Europe/Prague');


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
));


// using manual hints
$res = dibi::query('SELECT * FROM [customers]');

$res->setType('customer_id', Dibi::INTEGER)
	->setType('added', Dibi::DATETIME)
	->setFormat(dibi::DATETIME, 'Y-m-d H:i:s');


dump( $res->fetch() );
// outputs:
// DibiRow(3) {
//    customer_id => 1
//    name => "Dave Lister" (11)
//    added => "2007-03-11 17:20:03" (19)


// using auto-detection (works well with MySQL or other strictly typed databases)
$res = dibi::query('SELECT * FROM [customers]');

dump( $res->fetch() );
// outputs:
// DibiRow(3) {
//    customer_id => 1
//    name => "Dave Lister" (11)
//    added => "2007-03-11 17:20:03" (19)
