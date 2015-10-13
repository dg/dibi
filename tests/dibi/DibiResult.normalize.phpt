<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class MockResult extends DibiResult
{
	function __construct()
	{}

	function test($row)
	{
		$normalize = new ReflectionMethod('DibiResult', 'normalize');
		$normalize->setAccessible(TRUE);
		$normalize->invokeArgs($this, array(& $row));
		return $row;
	}
}


test(function () {
	$result = new MockResult;
	$result->setType('col', dibi::BOOL);

	Assert::same(array('col' => NULL), $result->test(array('col' => NULL)));
	Assert::same(array('col' => TRUE), $result->test(array('col' => TRUE)));
	Assert::same(array('col' => FALSE), $result->test(array('col' => FALSE)));

	Assert::same(array('col' => FALSE), $result->test(array('col' => '')));
	Assert::same(array('col' => FALSE), $result->test(array('col' => '0')));
	Assert::same(array('col' => TRUE), $result->test(array('col' => '1')));
	Assert::same(array('col' => TRUE), $result->test(array('col' => 't')));
	Assert::same(array('col' => FALSE), $result->test(array('col' => 'f')));
	Assert::same(array('col' => TRUE), $result->test(array('col' => 'T')));
	Assert::same(array('col' => FALSE), $result->test(array('col' => 'F')));
	Assert::same(array('col' => FALSE), $result->test(array('col' => 0)));
	Assert::same(array('col' => FALSE), $result->test(array('col' => 0.0)));
	Assert::same(array('col' => TRUE), $result->test(array('col' => 1)));
	Assert::same(array('col' => TRUE), $result->test(array('col' => 1.0)));
});


test(function () {
	$result = new MockResult;
	$result->setType('col', dibi::TEXT); // means TEXT or UNKNOWN

	Assert::same(array('col' => NULL), $result->test(array('col' => NULL)));
	Assert::same(array('col' => TRUE), $result->test(array('col' => TRUE)));
	Assert::same(array('col' => FALSE), $result->test(array('col' => FALSE)));

	Assert::same(array('col' => ''), $result->test(array('col' => '')));
	Assert::same(array('col' => '0'), $result->test(array('col' => '0')));
	Assert::same(array('col' => '1'), $result->test(array('col' => '1')));
	Assert::same(array('col' => 0), $result->test(array('col' => 0)));
	Assert::same(array('col' => 1), $result->test(array('col' => 1)));
});


test(function () {
	$result = new MockResult;
	$result->setType('col', dibi::FLOAT);

	Assert::same(array('col' => NULL), $result->test(array('col' => NULL)));
	Assert::same(array('col' => 1.0), $result->test(array('col' => TRUE)));
	Assert::same(array('col' => 0.0), $result->test(array('col' => FALSE)));

	Assert::same(array('col' => 0.0), $result->test(array('col' => '')));
	Assert::same(array('col' => 0.0), $result->test(array('col' => '0')));
	Assert::same(array('col' => 1.0), $result->test(array('col' => '1')));
	Assert::same(array('col' => 0.0), $result->test(array('col' => '.0')));
	Assert::same(array('col' => 0.1), $result->test(array('col' => '.1')));
	Assert::same(array('col' => 0.0), $result->test(array('col' => '0.0')));
	Assert::same(array('col' => 0.1), $result->test(array('col' => '0.1')));
	Assert::same(array('col' => 0.0), $result->test(array('col' => '0.000')));
	Assert::same(array('col' => 0.1), $result->test(array('col' => '0.100')));
	Assert::same(array('col' => 1.0), $result->test(array('col' => '1.0')));
	Assert::same(array('col' => 1.1), $result->test(array('col' => '1.1')));
	Assert::same(array('col' => 1.0), $result->test(array('col' => '1.000')));
	Assert::same(array('col' => 1.1), $result->test(array('col' => '1.100')));
	Assert::same(array('col' => 1.0), $result->test(array('col' => '001.000')));
	Assert::same(array('col' => 1.1), $result->test(array('col' => '001.100')));
	Assert::same(array('col' => 10.0), $result->test(array('col' => '10')));
	Assert::same(array('col' => 11.0), $result->test(array('col' => '11')));
	Assert::same(array('col' => 10.0), $result->test(array('col' => '0010')));
	Assert::same(array('col' => 11.0), $result->test(array('col' => '0011')));
	Assert::same(array('col' => '0.00000000000000000001'), $result->test(array('col' => '0.00000000000000000001')));
	Assert::same(array('col' => '12345678901234567890'), $result->test(array('col' => '12345678901234567890')));
	Assert::same(array('col' => '12345678901234567890'), $result->test(array('col' => '012345678901234567890')));
	Assert::same(array('col' => '12345678901234567890'), $result->test(array('col' => '12345678901234567890.000')));
	Assert::same(array('col' => '12345678901234567890.1'), $result->test(array('col' => '012345678901234567890.100')));

	Assert::same(array('col' => 0.0), $result->test(array('col' => 0)));
	Assert::same(array('col' => 0.0), $result->test(array('col' => 0.0)));
	Assert::same(array('col' => 1.0), $result->test(array('col' => 1)));
	Assert::same(array('col' => 1.0), $result->test(array('col' => 1.0)));

	setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'deu_deu');
	Assert::same(array('col' => 0.0), $result->test(array('col' => '')));
	Assert::same(array('col' => 0.0), $result->test(array('col' => '0')));
	Assert::same(array('col' => 1.0), $result->test(array('col' => '1')));
	Assert::same(array('col' => 0.0), $result->test(array('col' => '.0')));
	Assert::same(array('col' => 0.1), $result->test(array('col' => '.1')));
	Assert::same(array('col' => 0.0), $result->test(array('col' => '0.0')));
	Assert::same(array('col' => 0.1), $result->test(array('col' => '0.1')));
	Assert::same(array('col' => 0.0), $result->test(array('col' => '0.000')));
	Assert::same(array('col' => 0.1), $result->test(array('col' => '0.100')));
	Assert::same(array('col' => 1.0), $result->test(array('col' => '1.0')));
	Assert::same(array('col' => 1.1), $result->test(array('col' => '1.1')));
	Assert::same(array('col' => 1.0), $result->test(array('col' => '1.000')));
	Assert::same(array('col' => 1.1), $result->test(array('col' => '1.100')));
	Assert::same(array('col' => 1.0), $result->test(array('col' => '001.000')));
	Assert::same(array('col' => 1.1), $result->test(array('col' => '001.100')));
	Assert::same(array('col' => 10.0), $result->test(array('col' => '10')));
	Assert::same(array('col' => 11.0), $result->test(array('col' => '11')));
	Assert::same(array('col' => 10.0), $result->test(array('col' => '0010')));
	Assert::same(array('col' => 11.0), $result->test(array('col' => '0011')));
	Assert::same(array('col' => '0.00000000000000000001'), $result->test(array('col' => '0.00000000000000000001')));
	Assert::same(array('col' => '12345678901234567890'), $result->test(array('col' => '12345678901234567890')));
	Assert::same(array('col' => '12345678901234567890'), $result->test(array('col' => '012345678901234567890')));
	Assert::same(array('col' => '12345678901234567890'), $result->test(array('col' => '12345678901234567890.000')));
	Assert::same(array('col' => '12345678901234567890.1'), $result->test(array('col' => '012345678901234567890.100')));

	Assert::same(array('col' => 0.0), $result->test(array('col' => 0)));
	Assert::same(array('col' => 0.0), $result->test(array('col' => 0.0)));
	Assert::same(array('col' => 1.0), $result->test(array('col' => 1)));
	Assert::same(array('col' => 1.0), $result->test(array('col' => 1.0)));
	setlocale(LC_NUMERIC, 'C');
});
