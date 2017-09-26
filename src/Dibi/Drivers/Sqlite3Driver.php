<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;
use SQLite3;


/**
 * The dibi driver for SQLite3 database.
 *
 * Driver options:
 *   - database (or file) => the filename of the SQLite3 database
 *   - formatDate => how to format date in SQL (@see date)
 *   - formatDateTime => how to format datetime in SQL (@see date)
 *   - dbcharset => database character encoding (will be converted to 'charset')
 *   - charset => character encoding to set (default is UTF-8)
 *   - resource (SQLite3) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class Sqlite3Driver implements Dibi\Driver, Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var SQLite3|null */
	private $connection;

	/** @var \SQLite3Result|null */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;

	/** @var string  Date and datetime format */
	private $fmtDate;

	private $fmtDateTime;

	/** @var string  character encoding */
	private $dbcharset;

	private $charset;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('sqlite3')) {
			throw new Dibi\NotSupportedException("PHP extension 'sqlite3' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config): void
	{
		Dibi\Helpers::alias($config, 'database', 'file');
		$this->fmtDate = $config['formatDate'] ?? 'U';
		$this->fmtDateTime = $config['formatDateTime'] ?? 'U';

		if (isset($config['resource']) && $config['resource'] instanceof SQLite3) {
			$this->connection = $config['resource'];
		} else {
			try {
				$this->connection = new SQLite3($config['database']);
			} catch (\Exception $e) {
				throw new Dibi\DriverException($e->getMessage(), $e->getCode());
			}
		}

		$this->dbcharset = empty($config['dbcharset']) ? 'UTF-8' : $config['dbcharset'];
		$this->charset = empty($config['charset']) ? 'UTF-8' : $config['charset'];
		if (strcasecmp($this->dbcharset, $this->charset) === 0) {
			$this->dbcharset = $this->charset = null;
		}

		// enable foreign keys support (defaultly disabled; if disabled then foreign key constraints are not enforced)
		$version = SQLite3::version();
		if ($version['versionNumber'] >= '3006019') {
			$this->query('PRAGMA foreign_keys = ON');
		}
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
	public function query(string $sql): ?Dibi\ResultDriver
	{
		if ($this->dbcharset !== null) {
			$sql = iconv($this->charset, $this->dbcharset . '//IGNORE', $sql);
		}

		$res = @$this->connection->query($sql); // intentionally @
		if ($code = $this->connection->lastErrorCode()) {
			throw self::createException($this->connection->lastErrorMsg(), $code, $sql);

		} elseif ($res instanceof \SQLite3Result && $res->numColumns()) {
			return $this->createResultDriver($res);
		}
		return null;
	}


	public static function createException(string $message, $code, string $sql): Dibi\DriverException
	{
		if ($code !== 19) {
			return new Dibi\DriverException($message, $code, $sql);

		} elseif (strpos($message, 'must be unique') !== false
			|| strpos($message, 'is not unique') !== false
			|| strpos($message, 'UNIQUE constraint failed') !== false
		) {
			return new Dibi\UniqueConstraintViolationException($message, $code, $sql);

		} elseif (strpos($message, 'may not be null') !== false
			|| strpos($message, 'NOT NULL constraint failed') !== false
		) {
			return new Dibi\NotNullConstraintViolationException($message, $code, $sql);

		} elseif (strpos($message, 'foreign key constraint failed') !== false
			|| strpos($message, 'FOREIGN KEY constraint failed') !== false
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
		return $this->connection->lastInsertRowID();
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(string $savepoint = null): void
	{
		$this->query($savepoint ? "SAVEPOINT $savepoint" : 'BEGIN');
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(string $savepoint = null): void
	{
		$this->query($savepoint ? "RELEASE SAVEPOINT $savepoint" : 'COMMIT');
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(string $savepoint = null): void
	{
		$this->query($savepoint ? "ROLLBACK TO SAVEPOINT $savepoint" : 'ROLLBACK');
	}


	/**
	 * Returns the connection resource.
	 */
	public function getResource(): SQLite3
	{
		return $this->connection;
	}


	/**
	 * Returns the connection reflector.
	 */
	public function getReflector(): Dibi\Reflector
	{
		return new SqliteReflector($this);
	}


	/**
	 * Result set driver factory.
	 */
	public function createResultDriver(\SQLite3Result $resource): Dibi\ResultDriver
	{
		$res = clone $this;
		$res->resultSet = $resource;
		return $res;
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


	public function escapeIdentifier(string $value): string
	{
		return '[' . strtr($value, '[]', '  ') . ']';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? '1' : '0';
	}


	/**
	 * @param  \DateTimeInterface|string|int
	 */
	public function escapeDate($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format($this->fmtDate);
	}


	/**
	 * @param  \DateTimeInterface|string|int
	 */
	public function escapeDateTime($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format($this->fmtDateTime);
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function escapeLike(string $value, int $pos): string
	{
		$value = addcslashes($this->connection->escapeString($value), '%_\\');
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'") . " ESCAPE '\\'";
	}


	/**
	 * Decodes data from result set.
	 */
	public function unescapeBinary(string $value): string
	{
		return $value;
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
	{
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($limit !== null || $offset) {
			$sql .= ' LIMIT ' . ($limit === null ? '-1' : $limit)
				. ($offset ? ' OFFSET ' . $offset : '');
		}
	}


	/********************* result set ****************d*g**/


	/**
	 * Automatically frees the resources allocated for this result set.
	 */
	public function __destruct()
	{
		$this->autoFree && $this->resultSet && @$this->free();
	}


	/**
	 * Returns the number of rows in a result set.
	 * @throws Dibi\NotSupportedException
	 */
	public function getRowCount(): int
	{
		throw new Dibi\NotSupportedException('Row count is not available for unbuffered queries.');
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		$row = $this->resultSet->fetchArray($assoc ? SQLITE3_ASSOC : SQLITE3_NUM);
		if (!$row) {
			return null;
		}
		$charset = $this->charset === null ? null : $this->charset . '//TRANSLIT';
		if ($row && ($assoc || $charset)) {
			$tmp = [];
			foreach ($row as $k => $v) {
				if ($charset !== null && is_string($v)) {
					$v = iconv($this->dbcharset, $charset, $v);
				}
				$tmp[str_replace(['[', ']'], '', $k)] = $v;
			}
			return $tmp;
		}
		return $row;
	}


	/**
	 * Moves cursor position without fetching row.
	 * @throws Dibi\NotSupportedException
	 */
	public function seek(int $row): bool
	{
		throw new Dibi\NotSupportedException('Cannot seek an unbuffered result set.');
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		$this->resultSet->finalize();
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = $this->resultSet->numColumns();
		$columns = [];
		static $types = [SQLITE3_INTEGER => 'int', SQLITE3_FLOAT => 'float', SQLITE3_TEXT => 'text', SQLITE3_BLOB => 'blob', SQLITE3_NULL => 'null'];
		for ($i = 0; $i < $count; $i++) {
			$columns[] = [
				'name' => $this->resultSet->columnName($i),
				'table' => null,
				'fullname' => $this->resultSet->columnName($i),
				'nativetype' => $types[$this->resultSet->columnType($i)],
			];
		}
		return $columns;
	}


	/**
	 * Returns the result set resource.
	 */
	public function getResultResource(): ?\SQLite3Result
	{
		$this->autoFree = false;
		return $this->resultSet;
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
	public function registerAggregateFunction(string $name, callable $rowCallback, callable $agrCallback, int $numArgs = -1): void
	{
		$this->connection->createAggregate($name, $rowCallback, $agrCallback, $numArgs);
	}
}
