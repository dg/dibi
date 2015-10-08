<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


/*Assert::exception(function () use ($conn) {
	$conn->rollback();
}, 'Dibi\Exception');

Assert::exception(function () use ($conn) {
	$conn->commit();
}, 'Dibi\Exception');

$conn->begin();
Assert::exception(function () use ($conn) {
	$conn->begin();
}, 'Dibi\Exception');
*/


$conn->begin();
Assert::same(3, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
$conn->query('INSERT INTO [products]', [
	'title' => 'Test product',
]);
Assert::same(4, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
$conn->rollback();
Assert::same(3, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());




$conn->begin();
$conn->query('INSERT INTO [products]', [
	'title' => 'Test product',
]);
$conn->commit();
Assert::same(4, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
