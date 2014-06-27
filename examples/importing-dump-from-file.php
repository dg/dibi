<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Importing SQL Dump from File | dibi</h1>

<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
));


$count = dibi::loadFile('compress.zlib://data/sample.dump.sql.gz');

echo 'Number of SQL commands:', $count;
