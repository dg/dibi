<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Nette Debugger & SQL Exceptions | dibi</h1>

<p>Dibi can display and log exceptions via Nette Debugger, part of Nette Framework.</p>

<ul>
	<li>Nette Framework: http://nette.org
</ul>

<?php

require_once 'Nette/Debugger.php';
require_once '../dibi/dibi.php';


// enable Nette Debugger
ndebug();


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
	'profiler' => array(
		'run' => TRUE,
	)
));


// throws error because SQL is bad
dibi::query('SELECT FROM customers WHERE customer_id < ?', 38);
