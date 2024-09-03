<?php

/**
 * @dataProvider? ../databases.ini sqlsrv
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$tests = function ($conn) {
	// Limit and offset
	Assert::same(
		'SELECT 1 OFFSET 10 ROWS FETCH NEXT 10 ROWS ONLY',
		$conn->translate('SELECT 1 %ofs %lmt', 10, 10),
	);

	// Limit only
	Assert::same(
		'SELECT 1 OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY',
		$conn->translate('SELECT 1 %lmt', 10),
	);

	// Offset only
	Assert::same(
		'SELECT 1 OFFSET 10 ROWS',
		$conn->translate('SELECT 1 %ofs', 10),
	);

	// Offset invalid
	Assert::error(
		function () use ($conn) {
			$conn->translate('SELECT 1 %ofs', -10);
		},
		Dibi\NotSupportedException::class,
		'Negative offset or limit.',
	);

	// Limit invalid
	Assert::error(
		function () use ($conn) {
			$conn->translate('SELECT 1 %lmt', -10);
		},
		Dibi\NotSupportedException::class,
		'Negative offset or limit.',
	);

	// Limit invalid, offset valid
	Assert::error(
		function () use ($conn) {
			$conn->translate('SELECT 1 %ofs %lmt', 10, -10);
		},
		Dibi\NotSupportedException::class,
		'Negative offset or limit.',
	);

	// Limit valid, offset invalid
	Assert::error(
		function () use ($conn) {
			$conn->translate('SELECT 1 %ofs %lmt', -10, 10);
		},
		Dibi\NotSupportedException::class,
		'Negative offset or limit.',
	);
};

$conn = new Dibi\Connection($config);
$tests($conn);
