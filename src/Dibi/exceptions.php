<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * Dibi common exception.
 */
class Exception extends \Exception
{
	private ?string $sql;


	public function __construct(
		string $message = '',
		int|string $code = 0,
		?string $sql = null,
		?\Throwable $previous = null,
	) {
		parent::__construct($message, 0, $previous);
		$this->code = $code;
		$this->sql = $sql;
	}


	final public function getSql(): ?string
	{
		return $this->sql;
	}


	public function __toString(): string
	{
		return parent::__toString() . ($this->sql ? "\nSQL: " . $this->sql : '');
	}
}


/**
 * database server exception.
 */
class DriverException extends Exception
{
}


/**
 * PCRE exception.
 */
class PcreException extends Exception
{
	public function __construct()
	{
		parent::__construct(preg_last_error_msg(), preg_last_error());
	}
}


class NotImplementedException extends Exception
{
}


class NotSupportedException extends Exception
{
}


/**
 * Database procedure exception.
 */
class ProcedureException extends Exception
{
	protected string $severity;


	/**
	 * Construct the exception.
	 */
	public function __construct(string $message = '', int $code = 0, string $severity = '', ?string $sql = null)
	{
		parent::__construct($message, $code, $sql);
		$this->severity = $severity;
	}


	/**
	 * Gets the exception severity.
	 */
	public function getSeverity(): string
	{
		return $this->severity;
	}
}


/**
 * Base class for all constraint violation related exceptions.
 */
class ConstraintViolationException extends DriverException
{
}


/**
 * Exception for a foreign key constraint violation.
 */
class ForeignKeyConstraintViolationException extends ConstraintViolationException
{
}


/**
 * Exception for a NOT NULL constraint violation.
 */
class NotNullConstraintViolationException extends ConstraintViolationException
{
}


/**
 * Exception for a unique constraint violation.
 */
class UniqueConstraintViolationException extends ConstraintViolationException
{
}
