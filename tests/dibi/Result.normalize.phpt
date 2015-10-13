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
		$normalize->invokeArgs($this, [& $row]);
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
