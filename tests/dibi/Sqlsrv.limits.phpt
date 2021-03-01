<?php

/**
 * @dataProvider? ../databases.ini sqlsrv
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$tests = function ($conn) {
	$resource = $conn->getDriver()->getResource();
	$version = is_resource($resource)
		? sqlsrv_server_info($resource)['SQLServerVersion']
		: $resource->getAttribute(PDO::ATTR_SERVER_VERSION);

	// MsSQL2012+
	if (version_compare($version, '11.0') >= 0) {
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
	} else {
		Assert::same(
			'SELECT TOP (1) * FROM (SELECT 1) t',
			$conn->translate('SELECT 1 %lmt', 1),
		);

		Assert::same(
			'SELECT 1',
			$conn->translate('SELECT 1 %lmt', -10),
		);

		Assert::exception(
			$conn->translate('SELECT 1 %ofs %lmt', 10, 10),
			Dibi\NotSupportedException::class,
		);
	}
};

$conn = new Dibi\Connection($config);
$tests($conn);
