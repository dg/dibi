<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 *
 * Copyright (c) 2005 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */



/**
 * Result set single row.
 *
 * @author     David Grudl
 * @package    dibi
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
		if (!$time instanceof DibiDateTime) {
			if ((int) $time === 0) { // '', NULL, FALSE, '0000-00-00', ...
				return NULL;
			}
			$time = new DibiDateTime(is_numeric($time) ? date('Y-m-d H:i:s', $time) : $time);
		}
		return $format === NULL ? $time : $time->format($format);
	}



	/**
	 * Converts value to UNIX timestamp.
	 * @param  string key
	 * @return int
	 */
	public function asTimestamp($key)
	{
		trigger_error(__METHOD__ . '() is deprecated.', E_USER_WARNING);
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
		trigger_error(__METHOD__ . '() is deprecated.', E_USER_WARNING);
		return $this[$key];
	}



	/** @deprecated */
	public function asDate($key, $format = NULL)
	{
		trigger_error(__METHOD__ . '() is deprecated.', E_USER_WARNING);
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
