<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Dibi\Connection;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


test(function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());
});


test(function () use ($config) { // lazy
	$conn = new Connection($config + ['lazy' => true]);
	Assert::false($conn->isConnected());

	$conn->query('SELECT 1');
	Assert::true($conn->isConnected());
});


test(function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	Assert::null($conn->getConfig('lazy'));
	Assert::same($config['driver'], $conn->getConfig('driver'));
	Assert::type(Dibi\Driver::class, $conn->getDriver());
});


test(function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());
});
