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
	 * @param  string format (TRUE means DateTime object)
	 * @return mixed
	 */
	public function asDate($key, $format = NULL)
	{
		$time = $this[$key];
		if ($time == NULL) { // intentionally ==
			return NULL;

		} elseif ($format === NULL) { // return timestamp (default)
			return is_numeric($time) ? (int) $time : strtotime($time);

		} elseif ($format === TRUE) { // return DateTime object
			return new DateTime(is_numeric($time) ? date('Y-m-d H:i:s', $time) : $time);

		} elseif (is_numeric($time)) { // single timestamp
			return date($format, $time);

		} elseif (class_exists('DateTime', FALSE)) { // since PHP 5.2
			$time = new DateTime($time);
			return $time ? $time->format($format) : NULL;

		} else {
			return date($format, strtotime($time));
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
