<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Dibi\Connection;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


test('immediate connection and disconnection state', function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());
});


test('lazy connection initiated on first query', function () use ($config) {
	$conn = new Connection($config + ['lazy' => true]);
	Assert::false($conn->isConnected());

	$conn->query('SELECT 1');
	Assert::true($conn->isConnected());
});


test('config retrieval and driver instance access', function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	Assert::null($conn->getConfig('lazy'));
	Assert::same($config['driver'], $conn->getConfig('driver'));
	Assert::type(Dibi\Driver::class, $conn->getDriver());
});


test('idempotent disconnect calls', function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());
});


test('reconnect after disconnection', function () use ($config) {
	$conn = new Connection($config);
	Assert::equal('hello', $conn->query('SELECT %s', 'hello')->fetchSingle());

	$conn->disconnect();

	$conn->connect();
	Assert::equal('hello', $conn->query('SELECT %s', 'hello')->fetchSingle());
});


test('destructor disconnects active connection', function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	$conn->__destruct();
	Assert::false($conn->isConnected());
});


test('invalid onConnect option triggers exceptions', function () use ($config) {
	Assert::exception(
		fn() => new Connection($config + ['onConnect' => '']),
		InvalidArgumentException::class,
		"Configuration option 'onConnect' must be array.",
	);

	$e = Assert::exception(
		fn() => new Connection($config + ['onConnect' => ['STOP']]),
		Dibi\DriverException::class,
	);
	Assert::same('STOP', $e->getSql());

	$e = Assert::exception(
		fn() => new Connection($config + ['onConnect' => [['STOP %i', 123]]]),
		Dibi\DriverException::class,
	);
	Assert::same('STOP 123', $e->getSql());

	// lazy
	$conn = new Connection($config + ['lazy' => true, 'onConnect' => ['STOP']]);
	$e = Assert::exception(
		fn() => $conn->query('SELECT 1'),
		Dibi\DriverException::class,
	);
	Assert::same('STOP', $e->getSql());
});
