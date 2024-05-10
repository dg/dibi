<?php

declare(strict_types=1);

use Dibi\Type;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class MockResult extends Dibi\Result
{
	public function __construct()
	{
	}


	public function test($row)
	{
		$normalize = new ReflectionMethod(Dibi\Result::class, 'normalize');
		$normalize->setAccessible(true);
		$normalize->invokeArgs($this, [&$row]);
		return $row;
	}
}


test('', function () {
	$result = new MockResult;
	$result->setType('col', Type::Text);
	$result->setFormat(Type::Text, 'native');

	Assert::same(['col' => null], $result->test(['col' => null]));
	Assert::same(['col' => true], $result->test(['col' => true]));
	Assert::same(['col' => false], $result->test(['col' => false]));
});


test('', function () {
	$result = new MockResult;
	$result->setType('col', Type::Bool);

	Assert::same(['col' => null], $result->test(['col' => null]));
	Assert::same(['col' => true], $result->test(['col' => true]));
	Assert::same(['col' => false], $result->test(['col' => false]));

	Assert::same(['col' => false], $result->test(['col' => '']));
	Assert::same(['col' => false], $result->test(['col' => '0']));
	Assert::same(['col' => true], $result->test(['col' => '1']));
	Assert::same(['col' => true], $result->test(['col' => 't']));
	Assert::same(['col' => false], $result->test(['col' => 'f']));
	Assert::same(['col' => true], $result->test(['col' => 'T']));
	Assert::same(['col' => false], $result->test(['col' => 'F']));
	Assert::same(['col' => false], $result->test(['col' => 0]));
	Assert::same(['col' => false], $result->test(['col' => 0.0]));
	Assert::same(['col' => true], $result->test(['col' => 1]));
	Assert::same(['col' => true], $result->test(['col' => 1.0]));
});


test('', function () {
	$result = new MockResult;
	$result->setType('col', Type::Text);

	Assert::same(['col' => null], $result->test(['col' => null]));
	Assert::same(['col' => '1'], $result->test(['col' => true]));
	Assert::same(['col' => ''], $result->test(['col' => false]));

	Assert::same(['col' => ''], $result->test(['col' => '']));
	Assert::same(['col' => '0'], $result->test(['col' => '0']));
	Assert::same(['col' => '1'], $result->test(['col' => '1']));
	Assert::same(['col' => '0'], $result->test(['col' => 0]));
	Assert::same(['col' => '1'], $result->test(['col' => 1]));
});


test('', function () {
	$result = new MockResult;
	$result->setType('col', Type::Float);

	Assert::same(['col' => null], $result->test(['col' => null]));
	Assert::same(['col' => 1.0], $result->test(['col' => true]));
	Assert::same(['col' => 0.0], $result->test(['col' => false]));

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

	Assert::same(['col' => '1.1e+10'], $result->test(['col' => '1.1e+10']));
	Assert::same(['col' => '1.1e-10'], $result->test(['col' => '1.1e-10']));
	Assert::same(['col' => '1.1e+10'], $result->test(['col' => '001.1e+10']));
	Assert::notSame(['col' => '1.1e+1'], $result->test(['col' => '1.1e+10']));

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


test('', function () {
	$result = new MockResult;
	$result->setType('col', Type::Integer);

	Assert::same(['col' => null], $result->test(['col' => null]));
	Assert::same(['col' => 1], $result->test(['col' => true]));
	Assert::same(['col' => 0], $result->test(['col' => false]));

	if (PHP_VERSION_ID < 80000) {
		Assert::same(['col' => 0], @$result->test(['col' => ''])); // triggers warning since PHP 7.1
	} else {
		Assert::exception(
			fn() => Assert::same(['col' => 0], $result->test(['col' => ''])),
			TypeError::class,
		);
	}

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


test('', function () {
	$result = new MockResult;
	$result->setType('col', Type::DateTime);

	Assert::same(['col' => null], $result->test(['col' => null]));
	Assert::exception(
		fn() => $result->test(['col' => true]),
		TypeError::class,
	);
	Assert::same(['col' => null], $result->test(['col' => false]));

	Assert::same(['col' => null], $result->test(['col' => '']));
	Assert::same(['col' => null], $result->test(['col' => '0000-00-00']));
	Assert::equal(['col' => new Dibi\DateTime('00:00:00')], $result->test(['col' => '00:00:00']));
	Assert::equal(['col' => new Dibi\DateTime('2015-10-13')], $result->test(['col' => '2015-10-13']));
	Assert::equal(['col' => new Dibi\DateTime('2015-10-13 14:30')], $result->test(['col' => '2015-10-13 14:30']));
});


test('', function () {
	$result = new MockResult;
	$result->setType('col', Type::DateTime);
	$result->setFormat(Type::DateTime, 'Y-m-d H:i:s');

	Assert::same(['col' => null], $result->test(['col' => null]));
	Assert::exception(
		fn() => $result->test(['col' => true]),
		TypeError::class,
	);
	Assert::same(['col' => null], $result->test(['col' => false]));

	Assert::same(['col' => null], $result->test(['col' => '']));
	Assert::same(['col' => null], $result->test(['col' => '0000-00-00']));
	Assert::same(['col' => date('Y-m-d 00:00:00')], $result->test(['col' => '00:00:00']));
	Assert::equal(['col' => '2015-10-13 00:00:00'], $result->test(['col' => '2015-10-13']));
	Assert::equal(['col' => '2015-10-13 14:30:00'], $result->test(['col' => '2015-10-13 14:30']));
});


test('', function () {
	$result = new MockResult;
	$result->setType('col', Type::Date);

	Assert::same(['col' => null], $result->test(['col' => null]));
	Assert::exception(
		fn() => $result->test(['col' => true]),
		TypeError::class,
	);
	Assert::same(['col' => null], $result->test(['col' => false]));

	Assert::same(['col' => null], $result->test(['col' => '']));
	Assert::same(['col' => null], $result->test(['col' => '0000-00-00']));
	Assert::equal(['col' => new Dibi\DateTime('2015-10-13')], $result->test(['col' => '2015-10-13']));
});


test('', function () {
	$result = new MockResult;
	$result->setType('col', Type::Time);

	Assert::same(['col' => null], $result->test(['col' => null]));
	Assert::exception(
		fn() => $result->test(['col' => true]),
		TypeError::class,
	);
	Assert::same(['col' => null], $result->test(['col' => false]));

	Assert::same(['col' => null], $result->test(['col' => '']));
	Assert::same(['col' => null], $result->test(['col' => '0000-00-00']));
	Assert::equal(['col' => new Dibi\DateTime('00:00:00')], $result->test(['col' => '00:00:00']));
	Assert::equal(['col' => new Dibi\DateTime('14:30')], $result->test(['col' => '14:30']));
});
