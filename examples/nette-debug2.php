<!DOCTYPE html>

<h1>Nette\Debug & dibi example 2</h1>


<p>Dibi can dump variables via Nette\Debug, part of Nette Framework.</p>

<ul>
	<li>Nette Framework: http://nette.org
</ul>

<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


// enable Nette\Debug
Debug::enable();


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
	'profiler' => array(
		'run' => TRUE,
	)
));



// throws error
Debug::barDump( dibi::fetchAll('SELECT * FROM [customers] WHERE [customer_id] < %i', 38), '[customers]' );
