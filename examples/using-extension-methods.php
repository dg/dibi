<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Extension Methods | dibi</h1>

<?php

require dirname(__FILE__) . '/Nette/Debugger.php';
require dirname(__FILE__) . '/../dibi/dibi.php';

ndebug();

dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
));


// using the "prototype" to add custom method to class DibiResult
function DibiResult_prototype_fetchShuffle(DibiResult $obj)
{
	$all = $obj->fetchAll();
	shuffle($all);
	return $all;
}


// fetch complete result set shuffled
$res = dibi::query('SELECT * FROM [customers]');
$all = $res->fetchShuffle();
dump($all);
