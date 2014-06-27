<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<style> html { background: url(data/arrow.png) no-repeat bottom right; height: 100%; } </style>

<h1>Tracy & Variables | dibi</h1>

<p>Dibi can dump variables via Tracy, part of Nette Framework.</p>

<ul>
	<li>Tracy Debugger: http://tracy.nette.org
</ul>

<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install dependencies using `composer install --dev`');
}


Tracy\Debugger::enable();


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
	'profiler' => array(
		'run' => TRUE,
	)
));


Tracy\Debugger::barDump( dibi::fetchAll('SELECT * FROM customers WHERE customer_id < ?', 38), '[customers]' );
