<h1>dibi extension method example</h1>
<pre>
<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
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
Debug::dump($all);
