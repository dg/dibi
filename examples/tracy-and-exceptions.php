<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Tracy & SQL Exceptions | dibi</h1>

<p>Dibi can display and log exceptions via Tracy, part of Nette Framework.</p>

<ul>
	<li>Tracy Debugger: http://tracy.nette.org
</ul>

<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}


Tracy\Debugger::enable();


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
	'profiler' => array(
		'run' => TRUE,
	)
));


// throws error because SQL is bad
dibi::query('SELECT * FROM customers WHERE customer_id < ?', 38);


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
	'profiler' => array(
		'run' => TRUE,
	)
));


// throws error because SQL is bad
dibi::query('SELECT FROM customers WHERE customer_id < ?', 38);
