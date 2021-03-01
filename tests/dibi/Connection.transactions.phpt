<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new Dibi\Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


/*
Assert::exception(
	fn() => $conn->rollback(),
	Dibi\Exception::class,
);

Assert::exception(
	fn() => $conn->commit(),
	Dibi\Exception::class,
);

$conn->begin();
Assert::exception(
	fn() => $conn->begin(),
	Dibi\Exception::class,
);
*/


test('begin() & rollback()', function () use ($conn) {
	$conn->begin();
	Assert::same(3, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
	$conn->query('INSERT INTO [products]', [
		'title' => 'Test product',
	]);
	Assert::same(4, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
	$conn->rollback();
	Assert::same(3, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
});


test('begin() & commit()', function () use ($conn) {
	$conn->begin();
	$conn->query('INSERT INTO [products]', [
		'title' => 'Test product',
	]);
	$conn->commit();
	Assert::same(4, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
});


test('transaction() fail', function () use ($conn) {
	Assert::exception(
		fn() => $conn->transaction(function (Dibi\Connection $connection) {
			$connection->query('INSERT INTO [products]', [
				'title' => 'Test product',
			]);
			throw new Exception('my exception');
		}),
		Throwable::class,
		'my exception',
	);
	Assert::same(4, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
});


test('transaction() success', function () use ($conn) {
	$conn->transaction(function (Dibi\Connection $connection) {
		$connection->query('INSERT INTO [products]', [
			'title' => 'Test product',
		]);
	});
	Assert::same(5, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
});


test('nested transaction() call fail', function () use ($conn) {
	Assert::exception(
		fn() => $conn->transaction(function (Dibi\Connection $connection) {
			$connection->query('INSERT INTO [products]', [
				'title' => 'Test product',
			]);

			$connection->transaction(function (Dibi\Connection $connection2) {
				$connection2->query('INSERT INTO [products]', [
					'title' => 'Test product',
				]);
				throw new Exception('my exception');
			});
		}),
		Throwable::class,
		'my exception',
	);
	Assert::same(5, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
});


test('nested transaction() call success', function () use ($conn) {
	$conn->transaction(function (Dibi\Connection $connection) {
		$connection->query('INSERT INTO [products]', [
			'title' => 'Test product',
		]);

		$connection->transaction(function (Dibi\Connection $connection2) {
			$connection2->query('INSERT INTO [products]', [
				'title' => 'Test product',
			]);
		});
	});
	Assert::same(7, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
});


test('begin(), commit() & rollback() calls are forbidden in transaction()', function () use ($conn) {
	Assert::exception(
		fn() => $conn->transaction(function (Dibi\Connection $connection) {
			$connection->begin();
		}),
		LogicException::class,
		Dibi\Connection::class . '::begin() call is forbidden inside a transaction() callback',
	);

	Assert::exception(
		fn() => $conn->transaction(function (Dibi\Connection $connection) {
			$connection->commit();
		}),
		LogicException::class,
		Dibi\Connection::class . '::commit() call is forbidden inside a transaction() callback',
	);

	Assert::exception(
		fn() => $conn->transaction(function (Dibi\Connection $connection) {
			$connection->rollback();
		}),
		LogicException::class,
		Dibi\Connection::class . '::rollback() call is forbidden inside a transaction() callback',
	);
});
