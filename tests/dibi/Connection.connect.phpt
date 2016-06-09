<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;
use Dibi\Connection;

require __DIR__ . '/bootstrap.php';


test(function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());
});


test(function () use ($config) { // lazy
	$conn = new Connection($config + ['lazy' => TRUE]);
	Assert::false($conn->isConnected());

	$conn->query('SELECT 1');
	Assert::true($conn->isConnected());
});


test(function () use ($config) { // query string
	$conn = new Connection(http_build_query($config, NULL, '&'));
	Assert::true($conn->isConnected());

	Assert::null($conn->getConfig('lazy'));
	Assert::same($config['driver'], $conn->getConfig('driver'));
	Assert::type('Dibi\Driver', $conn->getDriver());
});


test(function () use ($config) {
	$conn = new Connection($config);
	Assert::true($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());
});
