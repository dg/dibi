<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


/**
 * SQL literal value.
 *
 * @package    dibi
 */
class DibiLiteral extends DibiObject
{
	/** @var string */
	private $value;


	public function __construct($value)
	{
		$this->value = (string) $value;
	}


	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->value;
	}

}
