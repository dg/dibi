<!DOCTYPE html>

<h1>Nette\Debug & dibi example</h1>


<p>Dibi can display and log exceptions via Nette\Debug, part of Nette Framework.</p>

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
dibi::query('SELECT FROM [customers] WHERE [customer_id] < %i', 38);
