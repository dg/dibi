<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * Data types.
 */
class Type
{
	public const
		Text = 's', // as 'string'
		Binary = 'bin',
		JSON = 'json',
		Bool = 'b',
		Integer = 'i',
		Float = 'f',
		Date = 'd',
		DateTime = 'dt',
		Time = 't',
		TimeInterval = 'ti';

	#[\Deprecated('use Type::Text')]
	public const TEXT = self::Text;

	#[\Deprecated('use Type::Binary')]
	public const BINARY = self::Binary;

	#[\Deprecated('use Type::Bool')]
	public const BOOL = self::Bool;

	#[\Deprecated('use Type::Integer')]
	public const INTEGER = self::Integer;

	#[\Deprecated('use Type::Float')]
	public const FLOAT = self::Float;

	#[\Deprecated('use Type::Date')]
	public const DATE = self::Date;

	#[\Deprecated('use Type::DateTime')]
	public const DATETIME = self::DateTime;

	#[\Deprecated('use Type::Time')]
	public const TIME = self::Time;

	#[\Deprecated('use Type::TimeInterval')]
	public const TIME_INTERVAL = self::TimeInterval;


	final public function __construct()
	{
		throw new \LogicException('Cannot instantiate static class ' . self::class);
	}
}
