<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Dibi\Connection;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


test('', function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());
});


test('lazy', function () use ($config) {
	$conn = new Connection($config + ['lazy' => true]);
	Assert::false($conn->isConnected());

	$conn->query('SELECT 1');
	Assert::true($conn->isConnected());
});


test('', function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	Assert::null($conn->getConfig('lazy'));
	Assert::same($config['driver'], $conn->getConfig('driver'));
	Assert::type(Dibi\Driver::class, $conn->getDriver());
});


test('', function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());
});


test('', function () use ($config) {
	$conn = new Connection($config);
	Assert::equal('hello', $conn->query('SELECT %s', 'hello')->fetchSingle());

	$conn->disconnect();

	$conn->connect();
	Assert::equal('hello', $conn->query('SELECT %s', 'hello')->fetchSingle());
});

test('Binding to int Query Parameters', function () use ($config) {
	$conn = new Connection($config);
	$driver = $conn->getDriver();
	try {
		Assert::equal(PHP_INT_MAX, intval($conn->query('SELECT %pq', $driver->bindInt(PHP_INT_MAX))->fetchSingle()));
		Assert::equal(42, intval($conn->query("SELECT 42;")->fetchSingle())); // Some PDO drivers will convert to string types even when bound to non string values
		Assert::null($conn->query('SELECT %pq', $driver->bindInt(null))->fetchSingle());
	} catch (Dibi\NotSupportedException $_) {
	}
});

test('Binding to Text Query Parameters', function () use ($config) {
	$conn = new Connection($config);
	$driver = $conn->getDriver();
	try {
		Assert::equal('hello', $conn->query('SELECT %pq', $driver->bindAsciiText('hello'))->fetchSingle());
		Assert::equal('hello', $conn->query('SELECT %pq', $driver->bindText('hello'))->fetchSingle());
		Assert::null($conn->query('SELECT %pq', $driver->bindText(null))->fetchSingle());
		Assert::null($conn->query('SELECT %pq', $driver->bindAsciiText(null))->fetchSingle());
	} catch (Dibi\NotSupportedException $_) {
	}
});

test('', function () use ($config) {
	Assert::exception(function () use ($config) {
		new Connection($config + ['onConnect' => '']);
	}, InvalidArgumentException::class, "Configuration option 'onConnect' must be array.");

	$e = Assert::exception(function () use ($config) {
		new Connection($config + ['onConnect' => ['STOP']]);
	}, Dibi\DriverException::class);
	Assert::same('STOP', $e->getSql());

	$e = Assert::exception(function () use ($config) {
		new Connection($config + ['onConnect' => [['STOP %i', 123]]]);
	}, Dibi\DriverException::class);
	Assert::same('STOP 123', $e->getSql());

	// lazy
	$conn = new Connection($config + ['lazy' => true, 'onConnect' => ['STOP']]);
	$e = Assert::exception(function () use ($conn) {
		$conn->query('SELECT 1');
	}, Dibi\DriverException::class);
	Assert::same('STOP', $e->getSql());
});
