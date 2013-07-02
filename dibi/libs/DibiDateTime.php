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
 * DateTime with serialization and timestamp support for PHP 5.2.
 *
 * @author     David Grudl
 * @package    dibi
 */
class DibiDateTime extends DateTime
{

	public function __construct($time = 'now', DateTimeZone $timezone = NULL)
	{
		if (is_numeric($time)) {
			$time = date('Y-m-d H:i:s', $time);
		}
		if ($timezone === NULL) {
			parent::__construct($time);
		} else {
			parent::__construct($time, $timezone);
		}
	}


	public function modifyClone($modify = '')
	{
		$dolly = clone($this);
		return $modify ? $dolly->modify($modify) : $dolly;
	}


	public function modify($modify)
	{
		parent::modify($modify);
		return $this;
	}


	public function __sleep()
	{
		$this->fix = array($this->format('Y-m-d H:i:s'), $this->getTimezone()->getName());
		return array('fix');
	}


	public function __wakeup()
	{
		$this->__construct($this->fix[0], new DateTimeZone($this->fix[1]));
		unset($this->fix);
	}


	public function getTimestamp()
	{
		return (int) $this->format('U');
	}


	public function setTimestamp($timestamp)
	{
		return $this->__construct(date('Y-m-d H:i:s', $timestamp), new DateTimeZone($this->getTimezone()->getName())); // getTimeZone() crashes in PHP 5.2.6
	}


	public function __toString()
	{
		return $this->format('Y-m-d H:i:s');
	}

}
