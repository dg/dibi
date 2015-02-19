<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

if ($config['system'] !== 'mssql' || $config['driver'] !== 'pdo') {
	Tester\Environment::skip("Not supported system '$config[system]'.");
}

$tests = function($conn){
	$version = $conn->getDriver()->getResource()->getAttribute(PDO::ATTR_SERVER_VERSION);

	// MsSQL2012+
	if(version_compare($version, '11.0') >= 0) {
		// Limit and offset
		Assert::same(
			'SELECT 1 OFFSET 10 ROWS FETCH NEXT 10 ROWS ONLY',
			$conn->translate('SELECT 1 %ofs %lmt', 10, 10)
		);

		// Limit only
		Assert::same(
			'SELECT 1 OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY',
			$conn->translate('SELECT 1 %lmt', 10)
		);

		// Offset only
		Assert::same(
			'SELECT 1 OFFSET 10 ROWS',
			$conn->translate('SELECT 1 %ofs', 10)
		);

		// Offset invalid
		Assert::same(
			'SELECT 1',
			$conn->translate('SELECT 1 %ofs', -10)
		);

		// Limit invalid
		Assert::same(
			'SELECT 1',
			$conn->translate('SELECT 1 %lmt', -10)
		);

		// Limit invalid, offset valid
		Assert::same(
			'SELECT 1',
			$conn->translate('SELECT 1 %ofs %lmt', 10, -10)
		);

		// Limit valid, offset invalid
		Assert::same(
			'SELECT 1',
			$conn->translate('SELECT 1 %ofs %lmt', -10, 10)
		);
	} else {
		Assert::same(
			'SELECT TOP 1 * FROM (SELECT 1) t',
			$conn->translate('SELECT 1 %lmt', 1)
		);

		Assert::same(
			'SELECT 1',
			$conn->translate('SELECT 1 %lmt', -10)
		);

		Assert::exception(
			$conn->translate('SELECT 1 %ofs %lmt', 10, 10),
			'DibiNotSupportedException'
		);
	}
};

$conn = new DibiConnection($config);
$tests($conn);
