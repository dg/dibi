<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * SQL literal value.
 */
class Literal
{
	private string $value;


	public function __construct($value)
	{
		$this->value = (string) $value;
	}


	public function __toString(): string
	{
		return $this->value;
	}
}
