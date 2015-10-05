<?php

/**
 * @dataProvider ../databases.ini mysqli
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new DibiConnection($config);
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
}, E_NOTICE, 'Undefined property: DibiRow::$missing');

Assert::error(function () use ($row) {
	$x = $row['missing'];
}, E_NOTICE, 'Undefined property: DibiRow::$missing');

Assert::false(isset($row->missing));
Assert::false(isset($row['missing']));


// to array
Assert::same(['product_id' => 1, 'title' => 'Chair'], iterator_to_array($row));
Assert::same(['product_id' => 1, 'title' => 'Chair'], $row->toArray());

// counting
Assert::same(2, count($row));
