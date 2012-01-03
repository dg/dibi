<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Nette Debugger & Variables | dibi</h1>

<p>Dibi can dump variables via Nette Debugger, part of Nette Framework.</p>

<ul>
	<li>Nette Framework: http://nette.org
</ul>

<?php

require_once 'Nette/Debugger.php';
require_once '../dibi/dibi.php';


// enable Nette Debugger
Debugger::enable();


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
	'profiler' => array(
		'run' => TRUE,
	)
));


Debugger::barDump( dibi::fetchAll('SELECT * FROM customers WHERE customer_id < ?', 38), '[customers]' );
