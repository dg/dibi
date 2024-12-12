<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * A database operation failed.
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
 * The database server reported an error.
 */
class DriverException extends Exception
{
}


/**
 * Regular expression pattern or execution failed.
 */
class PcreException extends Exception
{
	public function __construct()
	{
		parent::__construct(preg_last_error_msg(), preg_last_error());
	}
}


/**
 * The requested feature is not implemented.
 */
class NotImplementedException extends Exception
{
}


/**
 * The requested operation is not supported.
 */
class NotSupportedException extends Exception
{
}


/**
 * A database stored procedure failed.
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
 * A database constraint was violated.
 */
class ConstraintViolationException extends DriverException
{
}


/**
 * The foreign key constraint check failed.
 */
class ForeignKeyConstraintViolationException extends ConstraintViolationException
{
}


/**
 * The NOT NULL constraint check failed.
 */
class NotNullConstraintViolationException extends ConstraintViolationException
{
}


/**
 * The unique constraint check failed.
 */
class UniqueConstraintViolationException extends ConstraintViolationException
{
}
