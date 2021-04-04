<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


/*Assert::exception(function () use ($conn) {
	$conn->rollback();
}, Dibi\Exception::class);

Assert::exception(function () use ($conn) {
	$conn->commit();
}, Dibi\Exception::class);

$conn->begin();
Assert::exception(function () use ($conn) {
	$conn->begin();
}, Dibi\Exception::class);
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



Assert::exception(function () use ($conn) {
	$conn->transaction(function (Dibi\Connection $connection) {
		$connection->query('INSERT INTO [products]', [
			'title' => 'Test product',
		]);
		throw new Exception('my exception');
	});
}, \Throwable::class, 'my exception');

Assert::same(4, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());

$conn->transaction(function (Dibi\Connection $connection) {
	$connection->query('INSERT INTO [products]', [
		'title' => 'Test product',
	]);
});

Assert::same(5, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
