<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new DibiConnection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");


/*Assert::exception(function() use ($conn) {
	$conn->rollback();
}, 'DibiException');

Assert::exception(function() use ($conn) {
	$conn->commit();
}, 'DibiException');

$conn->begin();
Assert::exception(function() use ($conn) {
	$conn->begin();
}, 'DibiException');
*/


$conn->begin();
Assert::same(3, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
$conn->query('INSERT INTO [products]', array(
	'title' => 'Test product',
));
Assert::same(4, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
$conn->rollback();
Assert::same(3, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());




$conn->begin();
$conn->query('INSERT INTO [products]', array(
	'title' => 'Test product',
));
$conn->commit();
Assert::same(4, (int) $conn->query('SELECT COUNT(*) FROM [products]')->fetchSingle());
