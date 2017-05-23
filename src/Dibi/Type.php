<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * Data types.
 */
class Type
{
	const
		TEXT = 's', // as 'string'
		BINARY = 'bin',
		BOOL = 'b',
		INTEGER = 'i',
		FLOAT = 'f',
		DATE = 'd',
		DATETIME = 'dt',
		TIME = 't',
		TIME_INTERVAL = 'ti',
		ARRAY_TYPE = 'a',
		JSON = 'j',
		JSONB = 'jb';

	final public function __construct()
	{
		throw new \LogicException('Cannot instantiate static class ' . __CLASS__);
	}

}
