<h1>Dibi profiler example</h1>
<?php

require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
	'profiler' => TRUE,
));


for ($i=0; $i<20; $i++) {
	$res = dibi::query('SELECT * FROM [customers] WHERE [customer_id] < %i', $i);
}

?>

<p>Last query: <strong><?php echo dibi::$sql; ?></strong></p>

<p>Number of queries: <strong><?php echo dibi::$numOfQueries; ?></strong></p>

<p>Elapsed time for last query: <strong><?php echo sprintf('%0.3f', dibi::$elapsedTime * 1000); ?> ms</strong></p>

<p>Total elapsed time: <strong><?php echo sprintf('%0.3f', dibi::$totalTime * 1000); ?> ms</strong></p>
