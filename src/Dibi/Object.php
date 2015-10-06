<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


/**
 * Object is the ultimate ancestor of all instantiable classes.
 * @package    dibi
 */
abstract class DibiObject
{
	/** @var array [method => [type => callback]] */
	private static $extMethods;


	/**
	 * Call to undefined method.
	 * @throws LogicException
	 */
	public function __call($name, $args)
	{
		if ($cb = self::extensionMethod(get_class($this) . '::' . $name)) { // back compatiblity
			array_unshift($args, $this);
			return call_user_func_array($cb, $args);
		}
		$class = method_exists($this, $name) ? 'parent' : get_class($this);
		throw new LogicException("Call to undefined method $class::$name().");
	}


	/**
	 * Call to undefined static method.
	 * @throws LogicException
	 */
	public static function __callStatic($name, $args)
	{
		$class = get_called_class();
		throw new LogicException("Call to undefined static method $class::$name().");
	}


	/**
	 * Access to undeclared property.
	 * @throws LogicException
	 */
	public function &__get($name)
	{
		if ((method_exists($this, $m = 'get' . $name) || method_exists($this, $m = 'is' . $name))
			&& (new ReflectionMethod($this, $m))->isPublic()
		) { // back compatiblity
			$ret = $this->$m();
			return $ret;
		}
		$class = get_class($this);
		throw new LogicException("Attempt to read undeclared property $class::$$name.");
	}


	/**
	 * Access to undeclared property.
	 * @throws LogicException
	 */
	public function __set($name, $value)
	{
		$class = get_class($this);
		throw new LogicException("Attempt to write to undeclared property $class::$$name.");
	}


	/**
	 * @return bool
	 */
	public function __isset($name)
	{
		return FALSE;
	}


	/**
	 * Access to undeclared property.
	 * @throws LogicException
	 */
	public function __unset($name)
	{
		$class = get_class($this);
		throw new LogicException("Attempt to unset undeclared property $class::$$name.");
	}


	/**
	 * @param  string  method name
	 * @param  callabke
	 * @return mixed
	 */
	public static function extensionMethod($name, $callback = NULL)
	{
		if (strpos($name, '::') === FALSE) {
			$class = get_called_class();
		} else {
			list($class, $name) = explode('::', $name);
			$class = (new \ReflectionClass($class))->getName();
		}
		$list = & self::$extMethods[strtolower($name)];
		if ($callback === NULL) { // getter
			$cache = & $list[''][$class];
			if (isset($cache)) {
				return $cache;
			}

			foreach ([$class] + class_parents($class) + class_implements($class) as $cl) {
				if (isset($list[$cl])) {
					return $cache = $list[$cl];
				}
			}
			return $cache = FALSE;

		} else { // setter
			$list[$class] = $callback;
			$list[''] = NULL;
		}
	}

}
