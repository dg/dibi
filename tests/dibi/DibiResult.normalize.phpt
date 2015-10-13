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
