<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


test(function() use ($config) {
	$conn = new DibiConnection($config);
	Assert::true($conn->isConnected());

	$conn->disconnect();
	Assert::false($conn->isConnected());
});


test(function() use ($config) { // lazy
	$conn = new DibiConnection($config + array('lazy' => TRUE));
	Assert::false($conn->isConnected());

	$conn->query('SELECT 1');
	Assert::true($conn->isConnected());
});


test(function() use ($config) { // query string
	$conn = new DibiConnection(http_build_query($config, NULL, '&'));
	Assert::true($conn->isConnected());

	Assert::null($conn->getConfig('lazy'));
	Assert::same($config['driver'], $conn->getConfig('driver'));
	Assert::type('IDibiDriver', $conn->getDriver());
});
