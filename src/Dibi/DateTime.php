<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * DateTime.
 */
class DateTime extends \DateTime
{
	use Strict;

	public function __construct($time = 'now', \DateTimeZone $timezone = NULL)
	{
		if (is_numeric($time)) {
			parent::__construct('@' . $time);
			$this->setTimeZone($timezone ? $timezone : new \DateTimeZone(date_default_timezone_get()));
		} elseif ($timezone === NULL) {
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


	public function setTimestamp($timestamp)
	{
		$zone = $this->getTimezone();
		$this->__construct('@' . $timestamp);
		return $this->setTimeZone($zone);
	}


	public function getTimestamp()
	{
		$ts = $this->format('U');
		return is_float($tmp = $ts * 1) ? $ts : $tmp;
	}


	public function __toString()
	{
		return $this->format('Y-m-d H:i:s');
	}

}
