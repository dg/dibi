<?php

/**
 * @dataProvider? ../databases.ini postgre
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$tests = function ($conn) {
	Assert::false($conn->query("SELECT 'AAxBB' LIKE %~like~", 'A_B')->fetchSingle());
	Assert::true($conn->query("SELECT 'AA_BB' LIKE %~like~", 'A_B')->fetchSingle());

	Assert::false($conn->query("SELECT 'AAxBB' LIKE %~like~", 'A%B')->fetchSingle());
	Assert::true($conn->query("SELECT 'AA%BB' LIKE %~like~", 'A%B')->fetchSingle());

	Assert::same('AA\\BB', $conn->query("SELECT 'AA\\BB'")->fetchSingle());
	Assert::false($conn->query("SELECT 'AAxBB' LIKE %~like~", 'A\\B')->fetchSingle());
	Assert::true($conn->query("SELECT 'AA\\BB' LIKE %~like~", 'A\\B')->fetchSingle());
};

$conn = new Dibi\Connection($config);
$conn->query('SET escape_string_warning = off'); // do not log warnings

$conn->query('SET standard_conforming_strings = on');
$tests($conn);
$conn->query('SET standard_conforming_strings = off');
$tests($conn);
