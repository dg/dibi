<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
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

	/** @deprecated use Type::Text */
	public const TEXT = self::Text;

	/** @deprecated use Type::Binary */
	public const BINARY = self::Binary;

	/** @deprecated use Type::Bool */
	public const BOOL = self::Bool;

	/** @deprecated use Type::Integer */
	public const INTEGER = self::Integer;

	/** @deprecated use Type::Float */
	public const FLOAT = self::Float;

	/** @deprecated use Type::Date */
	public const DATE = self::Date;

	/** @deprecated use Type::DateTime */
	public const DATETIME = self::DateTime;

	/** @deprecated use Type::Time */
	public const TIME = self::Time;

	/** @deprecated use Type::TimeInterval */
	public const TIME_INTERVAL = self::TimeInterval;


	final public function __construct()
	{
		throw new \LogicException('Cannot instantiate static class ' . self::class);
	}
}
