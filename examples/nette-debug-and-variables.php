<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<style> html { background: url(data/arrow.png) no-repeat bottom right; height: 100%; } </style>

<h1>Nette Debugger & Variables | dibi</h1>

<p>Dibi can dump variables via Nette Debugger, part of Nette Framework.</p>

<ul>
	<li>Nette Framework: http://nette.org
</ul>

<?php

require dirname(__FILE__) . '/Nette/Debugger.php';
require dirname(__FILE__) . '/../dibi/dibi.php';


// enable Nette Debugger
NDebugger::enable();


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
	'profiler' => array(
		'run' => TRUE,
	)
));


NDebugger::barDump( dibi::fetchAll('SELECT * FROM customers WHERE customer_id < ?', 38), '[customers]' );
