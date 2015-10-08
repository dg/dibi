<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class TestClass
{
	use Dibi\Strict;

	public $public;

	protected $protected;

	public static $publicStatic;

	public function publicMethod()
	{}

	public static function publicMethodStatic()
	{}

	protected function protectedMethod()
	{}

	protected static function protectedMethodS()
	{}

	public function getBar()
	{
		return 123;
	}

	public function isFoo()
	{
		return 456;
	}
}

class TestChild extends TestClass
{
	public function callParent()
	{
		parent::callParent();
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

Assert::exception(function () {
	$obj = new TestChild;
	$obj->callParent();
}, 'LogicException', 'Call to undefined method parent::callParent().');

Assert::exception(function () {
	$obj = new TestClass;
	$obj->publicMethodX();
}, 'LogicException', 'Call to undefined method TestClass::publicMethodX(), did you mean publicMethod()?');

Assert::exception(function () { // suggest static method
	$obj = new TestClass;
	$obj->publicMethodStaticX();
}, 'LogicException', 'Call to undefined method TestClass::publicMethodStaticX(), did you mean publicMethodStatic()?');

Assert::exception(function () { // suggest only public method
	$obj = new TestClass;
	$obj->protectedMethodX();
}, 'LogicException', 'Call to undefined method TestClass::protectedMethodX().');


// writing
Assert::exception(function () {
	$obj = new TestClass;
	$obj->undeclared = 'value';
}, 'LogicException', 'Attempt to write to undeclared property TestClass::$undeclared.');

Assert::exception(function () {
	$obj = new TestClass;
	$obj->publicX = 'value';
}, 'LogicException', 'Attempt to write to undeclared property TestClass::$publicX, did you mean $public?');

Assert::exception(function () { // suggest only non-static property
	$obj = new TestClass;
	$obj->publicStaticX = 'value';
}, 'LogicException', 'Attempt to write to undeclared property TestClass::$publicStaticX.');

Assert::exception(function () { // suggest only public property
	$obj = new TestClass;
	$obj->protectedX = 'value';
}, 'LogicException', 'Attempt to write to undeclared property TestClass::$protectedX.');


// property getter
$obj = new TestClass;
Assert::false(isset($obj->bar));
Assert::same(123, $obj->bar);
Assert::false(isset($obj->foo));
Assert::same(456, $obj->foo);


// reading
Assert::exception(function () {
	$obj = new TestClass;
	$val = $obj->undeclared;
}, 'LogicException', 'Attempt to read undeclared property TestClass::$undeclared.');

Assert::exception(function () {
	$obj = new TestClass;
	$val = $obj->publicX;
}, 'LogicException', 'Attempt to read undeclared property TestClass::$publicX, did you mean $public?');

Assert::exception(function () { // suggest only non-static property
	$obj = new TestClass;
	$val = $obj->publicStaticX;
}, 'LogicException', 'Attempt to read undeclared property TestClass::$publicStaticX.');

Assert::exception(function () { // suggest only public property
	$obj = new TestClass;
	$val = $obj->protectedX;
}, 'LogicException', 'Attempt to read undeclared property TestClass::$protectedX.');


// unset/isset
Assert::exception(function () {
	$obj = new TestClass;
	unset($obj->undeclared);
}, 'LogicException', 'Attempt to unset undeclared property TestClass::$undeclared.');

Assert::false(isset($obj->undeclared));


// extension method
TestClass::extensionMethod('join', $func = function (TestClass $that, $separator) {
	return $that->foo . $separator . $that->bar;
});

$obj = new TestClass;
Assert::same('456*123', $obj->join('*'));
