<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Extension Methods | dibi</h1>

<?php

require __DIR__ . '/Tracy/tracy.phar';
require __DIR__ . '/../dibi/dibi.php';

Tracy\Debugger::enable();


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
));


// using the "prototype" to add custom method to class DibiResult
DibiResult::extensionMethod('fetchShuffle', function(DibiResult $obj)
{
	$all = $obj->fetchAll();
	shuffle($all);
	return $all;
});


// fetch complete result set shuffled
$res = dibi::query('SELECT * FROM [customers]');
$all = $res->fetchShuffle();
Tracy\Dumper::dump($all);
