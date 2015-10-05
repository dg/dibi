<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


/**
 * @package    dibi
 */
class DibiHelpers
{

	/** @internal */
	public static function escape($driver, $value, $type)
	{
		static $types = [
			dibi::TEXT => 'text',
			dibi::BINARY => 'binary',
			dibi::BOOL => 'bool',
			dibi::DATE => 'date',
			dibi::DATETIME => 'datetime',
			dibi::IDENTIFIER => 'identifier',
		];
		if (isset($types[$type])) {
			return $driver->{'escape' . $types[$type]}($value);
		} else {
			throw new InvalidArgumentException('Unsupported type.');
		}
	}

}
