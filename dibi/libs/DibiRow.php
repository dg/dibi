<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */



/**
 * Result-set single row.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
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
		if ((int) $time === 0) { // '', NULL, FALSE, '0000-00-00', ...
			return NULL;

		} elseif ($format === NULL) { // return timestamp (default)
			return is_numeric($time) ? (int) $time : strtotime($time);

		} elseif ($format === TRUE) { // return DateTime object
			return new DateTime53(is_numeric($time) ? date('Y-m-d H:i:s', $time) : $time);

		} elseif (is_numeric($time)) { // single timestamp
			return date($format, $time);

		} else {
			$time = new DateTime53($time);
			return $time->format($format);
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
