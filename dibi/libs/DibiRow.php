<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com
 *
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */



/**
 * Result-set single row.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiRow extends ArrayObject
{

	/**
	 * @param  array
	 */
	public function __construct($arr)
	{
		parent::__construct($arr, 2);
	}



	/**
	 * Converts value to date-time format.
	 * @param  string key
	 * @param  string format
	 * @return mixed
	 */
	public function asDate($key, $format = NULL)
	{
		$value = $this[$key];
		if ($value === NULL || $value === FALSE) {
			return $value;

		} else {
			$value = is_numeric($value) ? (int) $value : strtotime($value);
			return $format === NULL ? $value : date($format, $value);
		}
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



	/**
	 * PHP < 5.3 workaround
	 * @return void
	 */
	public function __wakeup()
	{
		$this->setFlags(2);
	}

}
