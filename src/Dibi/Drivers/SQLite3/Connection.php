<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers\SQLite3;

use Dibi;
use Dibi\Drivers;
use Dibi\Helpers;
use SQLite3;


/**
 * The driver for SQLite v3 database.
 *
 * Driver options:
 *   - database (or file) => the filename of the SQLite3 database
 *   - formatDate => how to format date in SQL (@see date)
 *   - formatDateTime => how to format datetime in SQL (@see date)
 *   - resource (SQLite3) => existing connection resource
 */
class Connection implements Drivers\Connection
{
	private SQLite3 $connection;
	private string $fmtDate;
	private string $fmtDateTime;


	/** @throws Dibi\NotSupportedException */
	public function __construct(array $config)
	{
		if (!extension_loaded('sqlite3')) {
			throw new Dibi\NotSupportedException("PHP extension 'sqlite3' is not loaded.");
		}

		if (isset($config['dbcharset']) || isset($config['charset'])) {
			throw new Dibi\NotSupportedException('Options dbcharset and charset are not longer supported.');
		}

		Helpers::alias($config, 'database', 'file');
		$this->fmtDate = $config['formatDate'] ?? 'U';
		$this->fmtDateTime = $config['formatDateTime'] ?? 'U';

		if (isset($config['resource']) && $config['resource'] instanceof SQLite3) {
			$this->connection = $config['resource'];
		} else {
			try {
				$this->connection = new SQLite3($config['database']);
			} catch (\Throwable $e) {
				throw new Dibi\DriverException($e->getMessage(), $e->getCode());
			}
		}

		// enable foreign keys support (defaultly disabled; if disabled then foreign key constraints are not enforced)
		$this->query('PRAGMA foreign_keys = ON');
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		$this->connection->close();
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Result
	{
		$res = @$this->connection->query($sql); // intentionally @
		if ($code = $this->connection->lastErrorCode()) {
			throw static::createException($this->connection->lastErrorMsg(), $code, $sql);

		} elseif ($res instanceof \SQLite3Result && $res->numColumns()) {
			return $this->createResultDriver($res);
		}

		return null;
	}


	public static function createException(string $message, $code, string $sql): Dibi\DriverException
	{
		if ($code !== 19) {
			return new Dibi\DriverException($message, $code, $sql);

		} elseif (str_contains($message, 'must be unique')
			|| str_contains($message, 'is not unique')
			|| str_contains($message, 'UNIQUE constraint failed')
		) {
			return new Dibi\UniqueConstraintViolationException($message, $code, $sql);

		} elseif (str_contains($message, 'may not be null')
			|| str_contains($message, 'NOT NULL constraint failed')
		) {
			return new Dibi\NotNullConstraintViolationException($message, $code, $sql);

		} elseif (str_contains($message, 'foreign key constraint failed')
			|| str_contains($message, 'FOREIGN KEY constraint failed')
		) {
			return new Dibi\ForeignKeyConstraintViolationException($message, $code, $sql);

		} else {
			return new Dibi\ConstraintViolationException($message, $code, $sql);
		}
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 */
	public function getAffectedRows(): ?int
	{
		return $this->connection->changes();
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 */
	public function getInsertId(?string $sequence): ?int
	{
		return $this->connection->lastInsertRowID() ?: null;
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(?string $savepoint = null): void
	{
		$this->query($savepoint ? "SAVEPOINT $savepoint" : 'BEGIN');
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(?string $savepoint = null): void
	{
		$this->query($savepoint ? "RELEASE SAVEPOINT $savepoint" : 'COMMIT');
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(?string $savepoint = null): void
	{
		$this->query($savepoint ? "ROLLBACK TO SAVEPOINT $savepoint" : 'ROLLBACK');
	}


	/**
	 * Returns the connection resource.
	 */
	public function getResource(): ?SQLite3
	{
		return $this->connection;
	}


	/**
	 * Returns the connection reflector.
	 */
	public function getReflector(): Drivers\Engine
	{
		return new Drivers\Engines\SQLiteEngine($this);
	}


	/**
	 * Result set driver factory.
	 */
	public function createResultDriver(\SQLite3Result $result): Result
	{
		return new Result($result);
	}


	/********************* SQL ****************d*g**/


	/**
	 * Encodes data for use in a SQL statement.
	 */
	public function escapeText(string $value): string
	{
		return "'" . $this->connection->escapeString($value) . "'";
	}


	public function escapeBinary(string $value): string
	{
		return "X'" . bin2hex($value) . "'";
	}


	/********************* user defined functions ****************d*g**/


	/**
	 * Registers an user defined function for use in SQL statements.
	 */
	public function registerFunction(string $name, callable $callback, int $numArgs = -1): void
	{
		$this->connection->createFunction($name, $callback, $numArgs);
	}


	/**
	 * Registers an aggregating user defined function for use in SQL statements.
	 */
	public function registerAggregateFunction(
		string $name,
		callable $rowCallback,
		callable $agrCallback,
		int $numArgs = -1,
	): void
	{
		$this->connection->createAggregate($name, $rowCallback, $agrCallback, $numArgs);
	}
}
