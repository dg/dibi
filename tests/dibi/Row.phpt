<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


$row = $conn->fetch('SELECT * FROM [products] ORDER BY product_id');

// existing
Assert::same('Chair', $row->title);
Assert::true(isset($row->title));
Assert::same('Chair', $row['title']);
Assert::true(isset($row['title']));


// missing
Assert::error(function () use ($row) {
	$x = $row->missing;
}, E_USER_NOTICE, "Attempt to read missing column 'missing'.");

Assert::error(function () use ($row) {
	$x = $row['missing'];
}, E_USER_NOTICE, "Attempt to read missing column 'missing'.");

Assert::false(isset($row->missing));
Assert::false(isset($row['missing']));


// suggestions
Assert::error(function () use ($row) {
	$x = $row->tilte;
}, E_USER_NOTICE, "Attempt to read missing column 'tilte', did you mean 'title'?");

Assert::error(function () use ($row) {
	$x = $row['tilte'];
}, E_USER_NOTICE, "Attempt to read missing column 'tilte', did you mean 'title'?");


// to array
Assert::same(['product_id' => num(1), 'title' => 'Chair'], iterator_to_array($row));
Assert::same(['product_id' => num(1), 'title' => 'Chair'], $row->toArray());

// counting
Assert::same(2, count($row));
