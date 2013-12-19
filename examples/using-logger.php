<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Logger | dibi</h1>

<?php

require __DIR__ . '/../dibi/dibi.php';

date_default_timezone_set('Europe/Prague');


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
	// enable query logging to this file
	'profiler' => array(
		'run' => TRUE,
		'file' => 'data/log.sql',
	),
));


try {
	$res = dibi::query('SELECT * FROM [customers] WHERE [customer_id] = ?', 1);

	$res = dibi::query('SELECT * FROM [customers] WHERE [customer_id] < ?', 5);

	$res = dibi::query('SELECT FROM [customers] WHERE [customer_id] < ?', 38);

} catch (DibiException $e) {
	echo '<p>', get_class($e), ': ', $e->getMessage(), '</p>';
}


// outputs a log file
echo "<h2>File data/log.sql:</h2>";

echo '<pre>', file_get_contents('data/log.sql'), '</pre>';
