<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

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
Assert::error(
	fn() => $x = $row->missing,
	E_USER_NOTICE,
	"Attempt to read missing column 'missing'.",
);

Assert::error(
	fn() => $x = $row['missing'],
	E_USER_NOTICE,
	"Attempt to read missing column 'missing'.",
);

Assert::false(isset($row->missing));
Assert::false(isset($row['missing']));

// ??
Assert::same(123, $row->missing ?? 123);
Assert::same(123, $row['missing'] ?? 123);


// suggestions
Assert::error(
	fn() => $x = $row->tilte,
	E_USER_NOTICE,
	"Attempt to read missing column 'tilte', did you mean 'title'?",
);

Assert::error(
	fn() => $x = $row['tilte'],
	E_USER_NOTICE,
	"Attempt to read missing column 'tilte', did you mean 'title'?",
);


// to array
Assert::same(['product_id' => num(1), 'title' => 'Chair'], iterator_to_array($row));
Assert::same(['product_id' => num(1), 'title' => 'Chair'], $row->toArray());

// counting
Assert::same(2, count($row));
