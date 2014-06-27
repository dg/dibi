<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Limit & Offset | dibi</h1>

<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
));


// no limit
dibi::test('SELECT * FROM [products]');
// -> SELECT * FROM [products]


// with limit = 2
dibi::test('SELECT * FROM [products] %lmt', 2);
// -> SELECT * FROM [products] LIMIT 2


// with limit = 2, offset = 1
dibi::test('SELECT * FROM [products] %lmt %ofs', 2, 1);
// -> SELECT * FROM [products] LIMIT 2 OFFSET 1
