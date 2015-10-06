<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


/**
 * Data types.
 */
class DibiType
{
	const
		TEXT = 's', // as 'string'
		BINARY = 'bin',
		BOOL = 'b',
		INTEGER = 'i',
		FLOAT = 'f',
		DATE = 'd',
		DATETIME = 't',
		TIME = 't';

	final public function __construct()
	{
		throw new LogicException('Cannot instantiate static class ' . __CLASS__);
	}

}
