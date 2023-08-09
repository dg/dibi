<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * SQL expression.
 */
class Expression
{
	private array $values;


	public function __construct(...$values)
	{
		$this->values = $values;
	}


	public function getValues(): array
	{
		return $this->values;
	}
}
