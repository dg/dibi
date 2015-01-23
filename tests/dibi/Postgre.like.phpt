<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

if ($config['system'] !== 'pgsql') {
	Tester\Environment::skip("Not supported system '$config[system]'.");
}


$tests = function($conn) {
	Assert::false($conn->query("SELECT 'AAxBB' LIKE %~like~", 'A_B')->fetchSingle());
	Assert::true( $conn->query("SELECT 'AA_BB' LIKE %~like~", 'A_B')->fetchSingle());

	Assert::false($conn->query("SELECT 'AAxBB' LIKE %~like~", 'A%B')->fetchSingle());
	Assert::true( $conn->query("SELECT 'AA%BB' LIKE %~like~", 'A%B')->fetchSingle());

	Assert::same('AA\\BB', $conn->query("SELECT 'AA\\BB'")->fetchSingle());
	Assert::false($conn->query("SELECT 'AAxBB' LIKE %~like~", 'A\\B')->fetchSingle());
	Assert::true( $conn->query("SELECT 'AA\\BB' LIKE %~like~", 'A\\B')->fetchSingle());
};

$conn = new DibiConnection($config);
$conn->query('SET escape_string_warning = off'); // do not log warnings

$conn->query('SET standard_conforming_strings = on');
$tests($conn);
$conn->query('SET standard_conforming_strings = off');
$tests($conn);
