<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi driver interacting with databases via ODBC connections.
 *
 * Driver options:
 *   - dsn => driver specific DSN
 *   - username (or user)
 *   - password (or pass)
 *   - persistent (bool) => try to find a persistent link?
 *   - resource (resource) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class OdbcDriver implements Dibi\Driver, Dibi\ResultDriver, Dibi\Reflector
{
	use Dibi\Strict;

	/** @var resource|null */
	private $connection;

	/** @var resource|null */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;

	/** @var int|null  Affected rows */
	private $affectedRows;

	/** @var int  Cursor */
	private $row = 0;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('odbc')) {
			throw new Dibi\NotSupportedException("PHP extension 'odbc' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config): void
	{
		if (isset($config['resource'])) {
			$this->connection = $config['resource'];
		} else {
			// default values
			$config += [
				'username' => ini_get('odbc.default_user'),
				'password' => ini_get('odbc.default_pw'),
				'dsn' => ini_get('odbc.default_db'),
			];

			if (empty($config['persistent'])) {
				$this->connection = @odbc_connect($config['dsn'], $config['username'] ?? '', $config['password'] ?? ''); // intentionally @
			} else {
				$this->connection = @odbc_pconnect($config['dsn'], $config['username'] ?? '', $config['password'] ?? ''); // intentionally @
			}
		}

		if (!is_resource($this->connection)) {
			throw new Dibi\DriverException(odbc_errormsg() . ' ' . odbc_error());
		}
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		@odbc_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Dibi\ResultDriver
	{
		$this->affectedRows = null;
		$res = @odbc_exec($this->connection, $sql); // intentionally @

		if ($res === false) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection), 0, $sql);

		} elseif (is_resource($res)) {
			$this->affectedRows = Dibi\Helpers::false2Null(odbc_num_rows($res));
			return $this->createResultDriver($res);
		}
		return null;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 */
	public function getAffectedRows(): ?int
	{
		return $this->affectedRows;
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 */
	public function getInsertId(?string $sequence): ?int
	{
		throw new Dibi\NotSupportedException('ODBC does not support autoincrementing.');
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(string $savepoint = null): void
	{
		if (!odbc_autocommit($this->connection, 0/*false*/)) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(string $savepoint = null): void
	{
		if (!odbc_commit($this->connection)) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}
		odbc_autocommit($this->connection, 1/*true*/);
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(string $savepoint = null): void
	{
		if (!odbc_rollback($this->connection)) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}
		odbc_autocommit($this->connection, 1/*true*/);
	}


	/**
	 * Is in transaction?
	 */
	public function inTransaction(): bool
	{
		return !odbc_autocommit($this->connection);
	}


	/**
	 * Returns the connection resource.
	 * @return resource|null
	 */
	public function getResource()
	{
		return is_resource($this->connection) ? $this->connection : null;
	}


	/**
	 * Returns the connection reflector.
	 */
	public function getReflector(): Dibi\Reflector
	{
		return $this;
	}


	/**
	 * Result set driver factory.
	 * @param  resource
	 */
	public function createResultDriver($resource): Dibi\ResultDriver
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
		return "'" . str_replace("'", "''", $value) . "'";
	}


	public function escapeBinary(string $value): string
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}


	public function escapeIdentifier(string $value): string
	{
		return '[' . str_replace(['[', ']'], ['[[', ']]'], $value) . ']';
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
		return $value->format("#m/d/Y#");
	}


	/**
	 * @param  \DateTimeInterface|string|int
	 */
	public function escapeDateTime($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format("#m/d/Y H:i:s.u#");
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function escapeLike(string $value, int $pos): string
	{
		$value = strtr($value, ["'" => "''", '%' => '[%]', '_' => '[_]', '[' => '[[]']);
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");
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
		if ($offset) {
			throw new Dibi\NotSupportedException('Offset is not supported by this database.');

		} elseif ($limit < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($limit !== null) {
			$sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ') t';
		}
	}


	/********************* result set ****************d*g**/


	/**
	 * Automatically frees the resources allocated for this result set.
	 */
	public function __destruct()
	{
		$this->autoFree && $this->getResultResource() && $this->free();
	}


	/**
	 * Returns the number of rows in a result set.
	 */
	public function getRowCount(): int
	{
		// will return -1 with many drivers :-(
		return odbc_num_rows($this->resultSet);
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		if ($assoc) {
			return Dibi\Helpers::false2Null(odbc_fetch_array($this->resultSet, ++$this->row));
		} else {
			$set = $this->resultSet;
			if (!odbc_fetch_row($set, ++$this->row)) {
				return null;
			}
			$count = odbc_num_fields($set);
			$cols = [];
			for ($i = 1; $i <= $count; $i++) {
				$cols[] = odbc_result($set, $i);
			}
			return $cols;
		}
	}


	/**
	 * Moves cursor position without fetching row.
	 */
	public function seek(int $row): bool
	{
		$this->row = $row;
		return true;
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		odbc_free_result($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = odbc_num_fields($this->resultSet);
		$columns = [];
		for ($i = 1; $i <= $count; $i++) {
			$columns[] = [
				'name' => odbc_field_name($this->resultSet, $i),
				'table' => null,
				'fullname' => odbc_field_name($this->resultSet, $i),
				'nativetype' => odbc_field_type($this->resultSet, $i),
			];
		}
		return $columns;
	}


	/**
	 * Returns the result set resource.
	 * @return resource|null
	 */
	public function getResultResource()
	{
		$this->autoFree = false;
		return is_resource($this->resultSet) ? $this->resultSet : null;
	}


	/********************* Dibi\Reflector ****************d*g**/


	/**
	 * Returns list of tables.
	 */
	public function getTables(): array
	{
		$res = odbc_tables($this->connection);
		$tables = [];
		while ($row = odbc_fetch_array($res)) {
			if ($row['TABLE_TYPE'] === 'TABLE' || $row['TABLE_TYPE'] === 'VIEW') {
				$tables[] = [
					'name' => $row['TABLE_NAME'],
					'view' => $row['TABLE_TYPE'] === 'VIEW',
				];
			}
		}
		odbc_free_result($res);
		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$res = odbc_columns($this->connection);
		$columns = [];
		while ($row = odbc_fetch_array($res)) {
			if ($row['TABLE_NAME'] === $table) {
				$columns[] = [
					'name' => $row['COLUMN_NAME'],
					'table' => $table,
					'nativetype' => $row['TYPE_NAME'],
					'size' => $row['COLUMN_SIZE'],
					'nullable' => (bool) $row['NULLABLE'],
					'default' => $row['COLUMN_DEF'],
				];
			}
		}
		odbc_free_result($res);
		return $columns;
	}


	/**
	 * Returns metadata for all indexes in a table.
	 */
	public function getIndexes(string $table): array
	{
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys(string $table): array
	{
		throw new Dibi\NotImplementedException;
	}
}
