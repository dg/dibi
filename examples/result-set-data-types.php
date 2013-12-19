<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Result Set Data Types | dibi</h1>

<?php

require __DIR__ . '/Tracy/tracy.phar';
require __DIR__ . '/../dibi/dibi.php';

Tracy\Debugger::enable();

date_default_timezone_set('Europe/Prague');


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
));


// using manual hints
$res = dibi::query('SELECT * FROM [customers]');

$res->setType('customer_id', Dibi::INTEGER)
	->setType('added', Dibi::DATETIME)
	->setFormat(dibi::DATETIME, 'Y-m-d H:i:s');


Tracy\Dumper::dump( $res->fetch() );
// outputs:
// DibiRow(3) {
//    customer_id => 1
//    name => "Dave Lister" (11)
//    added => "2007-03-11 17:20:03" (19)


// using auto-detection (works well with MySQL or other strictly typed databases)
$res = dibi::query('SELECT * FROM [customers]');

Tracy\Dumper::dump( $res->fetch() );
// outputs:
// DibiRow(3) {
//    customer_id => 1
//    name => "Dave Lister" (11)
//    added => "2007-03-11 17:20:03" (19)
