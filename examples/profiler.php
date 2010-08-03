<?php ob_start(1) // needed by FirePHP ?>

<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Profiler | dibi</h1>

<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
	'profiler' => TRUE,
));


// execute some queries...
for ($i=0; $i<20; $i++) {
	$res = dibi::query('SELECT * FROM [customers] WHERE [customer_id] < %i', $i);
}

// display output
?>
<p>Last query: <strong><?php echo dibi::$sql; ?></strong></p>

<p>Number of queries: <strong><?php echo dibi::$numOfQueries; ?></strong></p>

<p>Elapsed time for last query: <strong><?php echo sprintf('%0.3f', dibi::$elapsedTime * 1000); ?> ms</strong></p>

<p>Total elapsed time: <strong><?php echo sprintf('%0.3f', dibi::$totalTime * 1000); ?> ms</strong></p>

<br>

<p>Dibi can log to your Firebug Console. You first need to install the Firefox, Firebug and FirePHP extensions. You can install them from here:</p>

<ul>
	<li>Firebug: https://addons.mozilla.org/en-US/firefox/addon/1843
	<li>FirePHP: http://www.firephp.org/
</ul>