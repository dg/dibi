<?php

use Tester\Assert;
use Dibi\Type;

require __DIR__ . '/bootstrap.php';


class MockResult extends Dibi\Result
{
	function __construct()
	{}

	function test($row)
	{
		$normalize = new ReflectionMethod('Dibi\Result', 'normalize');
		$normalize->setAccessible(TRUE);
		$normalize->invokeArgs($this, [&$row]);
		return $row;
	}
}


test(function () {
	$result = new MockResult;
	$result->setType('col', Type::BOOL);

	Assert::same(['col' => NULL], $result->test(['col' => NULL]));
	Assert::same(['col' => TRUE], $result->test(['col' => TRUE]));
	Assert::same(['col' => FALSE], $result->test(['col' => FALSE]));

	Assert::same(['col' => FALSE], $result->test(['col' => '']));
	Assert::same(['col' => FALSE], $result->test(['col' => '0']));
	Assert::same(['col' => TRUE], $result->test(['col' => '1']));
	Assert::same(['col' => TRUE], $result->test(['col' => 't']));
	Assert::same(['col' => FALSE], $result->test(['col' => 'f']));
	Assert::same(['col' => TRUE], $result->test(['col' => 'T']));
	Assert::same(['col' => FALSE], $result->test(['col' => 'F']));
	Assert::same(['col' => FALSE], $result->test(['col' => 0]));
	Assert::same(['col' => FALSE], $result->test(['col' => 0.0]));
	Assert::same(['col' => TRUE], $result->test(['col' => 1]));
	Assert::same(['col' => TRUE], $result->test(['col' => 1.0]));
});


test(function () {
	$result = new MockResult;
	$result->setType('col', Type::TEXT);

	Assert::same(['col' => NULL], $result->test(['col' => NULL]));
	Assert::same(['col' => '1'], $result->test(['col' => TRUE]));
	Assert::same(['col' => ''], $result->test(['col' => FALSE]));

	Assert::same(['col' => ''], $result->test(['col' => '']));
	Assert::same(['col' => '0'], $result->test(['col' => '0']));
	Assert::same(['col' => '1'], $result->test(['col' => '1']));
	Assert::same(['col' => '0'], $result->test(['col' => 0]));
	Assert::same(['col' => '1'], $result->test(['col' => 1]));
});


test(function () {
	$result = new MockResult;
	$result->setType('col', Type::FLOAT);

	Assert::same(['col' => NULL], $result->test(['col' => NULL]));
	Assert::same(['col' => 1.0], $result->test(['col' => TRUE]));
	Assert::same(['col' => 0.0], $result->test(['col' => FALSE]));

	Assert::same(['col' => 0.0], $result->test(['col' => '']));
	Assert::same(['col' => 0.0], $result->test(['col' => '0']));
	Assert::same(['col' => 1.0], $result->test(['col' => '1']));
	Assert::same(['col' => 0.0], $result->test(['col' => '.0']));
	Assert::same(['col' => 0.1], $result->test(['col' => '.1']));
	Assert::same(['col' => 0.0], $result->test(['col' => '0.0']));
	Assert::same(['col' => 0.1], $result->test(['col' => '0.1']));
	Assert::same(['col' => 0.0], $result->test(['col' => '0.000']));
	Assert::same(['col' => 0.1], $result->test(['col' => '0.100']));
	Assert::same(['col' => 1.0], $result->test(['col' => '1.0']));
	Assert::same(['col' => 1.1], $result->test(['col' => '1.1']));
	Assert::same(['col' => 1.0], $result->test(['col' => '1.000']));
	Assert::same(['col' => 1.1], $result->test(['col' => '1.100']));
	Assert::same(['col' => 1.0], $result->test(['col' => '001.000']));
	Assert::same(['col' => 1.1], $result->test(['col' => '001.100']));
	Assert::same(['col' => 10.0], $result->test(['col' => '10']));
	Assert::same(['col' => 11.0], $result->test(['col' => '11']));
	Assert::same(['col' => 10.0], $result->test(['col' => '0010']));
	Assert::same(['col' => 11.0], $result->test(['col' => '0011']));
	Assert::same(['col' => '0.00000000000000000001'], $result->test(['col' => '0.00000000000000000001']));
	Assert::same(['col' => '12345678901234567890'], $result->test(['col' => '12345678901234567890']));
	Assert::same(['col' => '12345678901234567890'], $result->test(['col' => '012345678901234567890']));
	Assert::same(['col' => '12345678901234567890'], $result->test(['col' => '12345678901234567890.000']));
	Assert::same(['col' => '12345678901234567890.1'], $result->test(['col' => '012345678901234567890.100']));

	Assert::same(['col' => 0.0], $result->test(['col' => 0]));
	Assert::same(['col' => 0.0], $result->test(['col' => 0.0]));
	Assert::same(['col' => 1.0], $result->test(['col' => 1]));
	Assert::same(['col' => 1.0], $result->test(['col' => 1.0]));

	setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'deu_deu');
	Assert::same(['col' => 0.0], $result->test(['col' => '']));
	Assert::same(['col' => 0.0], $result->test(['col' => '0']));
	Assert::same(['col' => 1.0], $result->test(['col' => '1']));
	Assert::same(['col' => 0.0], $result->test(['col' => '.0']));
	Assert::same(['col' => 0.1], $result->test(['col' => '.1']));
	Assert::same(['col' => 0.0], $result->test(['col' => '0.0']));
	Assert::same(['col' => 0.1], $result->test(['col' => '0.1']));
	Assert::same(['col' => 0.0], $result->test(['col' => '0.000']));
	Assert::same(['col' => 0.1], $result->test(['col' => '0.100']));
	Assert::same(['col' => 1.0], $result->test(['col' => '1.0']));
	Assert::same(['col' => 1.1], $result->test(['col' => '1.1']));
	Assert::same(['col' => 1.0], $result->test(['col' => '1.000']));
	Assert::same(['col' => 1.1], $result->test(['col' => '1.100']));
	Assert::same(['col' => 1.0], $result->test(['col' => '001.000']));
	Assert::same(['col' => 1.1], $result->test(['col' => '001.100']));
	Assert::same(['col' => 10.0], $result->test(['col' => '10']));
	Assert::same(['col' => 11.0], $result->test(['col' => '11']));
	Assert::same(['col' => 10.0], $result->test(['col' => '0010']));
	Assert::same(['col' => 11.0], $result->test(['col' => '0011']));
	Assert::same(['col' => '0.00000000000000000001'], $result->test(['col' => '0.00000000000000000001']));
	Assert::same(['col' => '12345678901234567890'], $result->test(['col' => '12345678901234567890']));
	Assert::same(['col' => '12345678901234567890'], $result->test(['col' => '012345678901234567890']));
	Assert::same(['col' => '12345678901234567890'], $result->test(['col' => '12345678901234567890.000']));
	Assert::same(['col' => '12345678901234567890.1'], $result->test(['col' => '012345678901234567890.100']));

	Assert::same(['col' => 0.0], $result->test(['col' => 0]));
	Assert::same(['col' => 0.0], $result->test(['col' => 0.0]));
	Assert::same(['col' => 1.0], $result->test(['col' => 1]));
	Assert::same(['col' => 1.0], $result->test(['col' => 1.0]));
	setlocale(LC_NUMERIC, 'C');
});


test(function () {
	$result = new MockResult;
	$result->setType('col', Type::INTEGER);

	Assert::same(['col' => NULL], $result->test(['col' => NULL]));
	Assert::same(['col' => 1], $result->test(['col' => TRUE]));
	Assert::same(['col' => 0], $result->test(['col' => FALSE]));

	Assert::same(['col' => 0], @$result->test(['col' => ''])); // triggers warning in PHP 7.1
	Assert::same(['col' => 0], $result->test(['col' => '0']));
	Assert::same(['col' => 1], $result->test(['col' => '1']));
	Assert::same(['col' => 10], $result->test(['col' => '10']));
	Assert::same(['col' => 11], $result->test(['col' => '11']));
	Assert::same(['col' => 10], $result->test(['col' => '0010']));
	Assert::same(['col' => 11], $result->test(['col' => '0011']));
	Assert::same(['col' => '0.00000000000000000001'], $result->test(['col' => '0.00000000000000000001']));
	Assert::same(['col' => '12345678901234567890'], $result->test(['col' => '12345678901234567890']));
	Assert::same(['col' => '012345678901234567890'], $result->test(['col' => '012345678901234567890']));

	Assert::same(['col' => 0], $result->test(['col' => 0]));
	Assert::same(['col' => 0], $result->test(['col' => 0.0]));
	Assert::same(['col' => 1], $result->test(['col' => 1]));
	Assert::same(['col' => 1], $result->test(['col' => 1.0]));
});


test(function () {
	$result = new MockResult;
	$result->setType('col', Type::DATETIME);

	Assert::same(['col' => NULL], $result->test(['col' => NULL]));
	Assert::exception(function () use ($result) {
		$result->test(['col' => TRUE]);
	}, 'Exception');
	Assert::same(['col' => NULL], $result->test(['col' => FALSE]));

	Assert::same(['col' => NULL], $result->test(['col' => '']));
	Assert::same(['col' => NULL], $result->test(['col' => '0000-00-00']));
	Assert::equal(['col' => new Dibi\DateTime('00:00:00')], $result->test(['col' => '00:00:00']));
	Assert::equal(['col' => new Dibi\DateTime('2015-10-13')], $result->test(['col' => '2015-10-13']));
	Assert::equal(['col' => new Dibi\DateTime('2015-10-13 14:30')], $result->test(['col' => '2015-10-13 14:30']));
});


test(function () {
	$result = new MockResult;
	$result->setType('col', Type::DATETIME);
	$result->setFormat(Type::DATETIME, 'Y-m-d H:i:s');

	Assert::same(['col' => NULL], $result->test(['col' => NULL]));
	Assert::exception(function () use ($result) {
		$result->test(['col' => TRUE]);
	}, 'Exception');
	Assert::same(['col' => NULL], $result->test(['col' => FALSE]));

	Assert::same(['col' => NULL], $result->test(['col' => '']));
	Assert::same(['col' => NULL], $result->test(['col' => '0000-00-00']));
	Assert::same(['col' => date('Y-m-d 00:00:00')], $result->test(['col' => '00:00:00']));
	Assert::equal(['col' => '2015-10-13 00:00:00'], $result->test(['col' => '2015-10-13']));
	Assert::equal(['col' => '2015-10-13 14:30:00'], $result->test(['col' => '2015-10-13 14:30']));
});


test(function () {
	$result = new MockResult;
	$result->setType('col', Type::DATE);

	Assert::same(['col' => NULL], $result->test(['col' => NULL]));
	Assert::exception(function () use ($result) {
		$result->test(['col' => TRUE]);
	}, 'Exception');
	Assert::same(['col' => NULL], $result->test(['col' => FALSE]));

	Assert::same(['col' => NULL], $result->test(['col' => '']));
	Assert::same(['col' => NULL], $result->test(['col' => '0000-00-00']));
	Assert::equal(['col' => new Dibi\DateTime('2015-10-13')], $result->test(['col' => '2015-10-13']));
});


test(function () {
	$result = new MockResult;
	$result->setType('col', Type::TIME);

	Assert::same(['col' => NULL], $result->test(['col' => NULL]));
	Assert::exception(function () use ($result) {
		$result->test(['col' => TRUE]);
	}, 'Exception');
	Assert::same(['col' => NULL], $result->test(['col' => FALSE]));

	Assert::same(['col' => NULL], $result->test(['col' => '']));
	Assert::same(['col' => NULL], $result->test(['col' => '0000-00-00']));
	Assert::equal(['col' => new Dibi\DateTime('00:00:00')], $result->test(['col' => '00:00:00']));
	Assert::equal(['col' => new Dibi\DateTime('14:30')], $result->test(['col' => '14:30']));
});
