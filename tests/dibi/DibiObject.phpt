<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class TestClass extends DibiObject
{
	public function getBar()
	{
		return 123;
	}

	public function isFoo()
	{
		return 456;
	}
}


// calling
Assert::exception(function () {
	$obj = new TestClass;
	$obj->undeclared();
}, 'LogicException', 'Call to undefined method TestClass::undeclared().');

Assert::exception(function () {
	TestClass::undeclared();
}, 'LogicException', 'Call to undefined static method TestClass::undeclared().');


// writing
Assert::exception(function () {
	$obj = new TestClass;
	$obj->undeclared = 'value';
}, 'LogicException', 'Cannot assign to an undeclared property TestClass::$undeclared.');


// property getter
$obj = new TestClass;
Assert::true(isset($obj->bar));
Assert::same(123, $obj->bar);
Assert::false(isset($obj->foo));
Assert::same(456, $obj->foo);


// reading
Assert::exception(function () {
	$obj = new TestClass;
	$val = $obj->undeclared;
}, 'LogicException', 'Cannot read an undeclared property TestClass::$undeclared.');


// unset/isset
Assert::exception(function () {
	$obj = new TestClass;
	unset($obj->undeclared);
}, 'LogicException', 'Cannot unset the property TestClass::$undeclared.');

Assert::false(isset($obj->undeclared));


// extension method
TestClass::extensionMethod('join', $func = function (TestClass $that, $separator) {
	return $that->foo . $separator . $that->bar;
});

$obj = new TestClass;
Assert::same('456*123', $obj->join('*'));
