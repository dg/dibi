<h1>Nette::Debug && dibi example</h1>
<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';

Debug::enable();
Debug::enableProfiler();


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));



// throws error
dibi::query('SELECT FROM [customers] WHERE [customer_id] < %i', 38);
