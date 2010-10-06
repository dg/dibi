<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 *
 * Copyright (c) 2005, 2010 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license", and/or
 * GPL license. For more information please see http://dibiphp.com
 * @package    dibi
 */



/**
 * Result set single row.
 *
 * @author     David Grudl
 */
class DibiRow implements ArrayAccess, IteratorAggregate, Countable
{

	public function __construct($arr)
	{
		foreach ($arr as $k => $v) $this->$k = $v;
	}



	public function toArray()
	{
		return (array) $this;
	}



	/**
	 * Converts value to DateTime object.
	 * @param  string key
	 * @param  string format
	 * @return DateTime
	 */
	public function asDateTime($key, $format = NULL)
	{
		$time = $this[$key];
		if ((int) $time === 0) { // '', NULL, FALSE, '0000-00-00', ...
			return NULL;
		}
		$dt = new DibiDateTime(is_numeric($time) ? date('Y-m-d H:i:s', $time) : $time);
		return $format === NULL ? $dt : $dt->format($format);
	}



	/**
	 * Converts value to UNIX timestamp.
	 * @param  string key
	 * @return int
	 */
	public function asTimestamp($key)
	{
		$time = $this[$key];
		return (int) $time === 0 // '', NULL, FALSE, '0000-00-00', ...
			? NULL
			: (is_numeric($time) ? (int) $time : strtotime($time));
	}



	/**
	 * Converts value to boolean.
	 * @param  string key
	 * @return mixed
	 */
	public function asBool($key)
	{
		$value = $this[$key];
		if ($value === NULL || $value === FALSE) {
			return $value;

		} else {
			return ((bool) $value) && $value !== 'f' && $value !== 'F';
		}
	}



	/** @deprecated */
	public function asDate($key, $format = NULL)
	{
		if ($format === NULL) {
			return $this->asTimestamp($key);
		} else {
			return $this->asDateTime($key, $format === TRUE ? NULL : $format);
		}
	}



	/********************* interfaces ArrayAccess, Countable & IteratorAggregate ****************d*g**/



	final public function count()
	{
		return count((array) $this);
	}



	final public function getIterator()
	{
		return new ArrayIterator($this);
	}



	final public function offsetSet($nm, $val)
	{
		$this->$nm = $val;
	}



	final public function offsetGet($nm)
	{
		return $this->$nm;
	}



	final public function offsetExists($nm)
	{
		return isset($this->$nm);
	}



	final public function offsetUnset($nm)
	{
		unset($this->$nm);
	}

}
