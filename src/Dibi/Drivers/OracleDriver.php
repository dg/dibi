<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi driver for Oracle database.
 *
 * Driver options:
 *   - database => the name of the local Oracle instance or the name of the entry in tnsnames.ora
 *   - username (or user)
 *   - password (or pass)
 *   - charset => character encoding to set
 *   - schema => alters session schema
 *   - nativeDate => use native date format (defaults to true)
 *   - resource (resource) => existing connection resource
 *   - persistent => Creates persistent connections with oci_pconnect instead of oci_new_connect
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class OracleDriver implements Dibi\Driver, Dibi\ResultDriver, Dibi\Reflector
{
	use Dibi\Strict;

	/** @var resource|null */
	private $connection;

	/** @var resource|null */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;

	/** @var bool */
	private $autocommit = true;

	/** @var bool  use native datetime format */
	private $nativeDate;

	/** @var int|null Number of affected rows */
	private $affectedRows;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('oci8')) {
			throw new Dibi\NotSupportedException("PHP extension 'oci8' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config): void
	{
		$foo = &$config['charset'];

		if (isset($config['formatDate']) || isset($config['formatDateTime'])) {
			trigger_error('OracleDriver: options formatDate and formatDateTime are deprecated.', E_USER_DEPRECATED);
		}
		$this->nativeDate = $config['nativeDate'] ?? true;

		if (isset($config['resource'])) {
			$this->connection = $config['resource'];
		} elseif (empty($config['persistent'])) {
			$this->connection = @oci_new_connect($config['username'], $config['password'], $config['database'], $config['charset']); // intentionally @
		} else {
			$this->connection = @oci_pconnect($config['username'], $config['password'], $config['database'], $config['charset']); // intentionally @
		}

		if (!$this->connection) {
			$err = oci_error();
			throw new Dibi\DriverException($err['message'], $err['code']);
		}

		if (isset($config['schema'])) {
			$this->query('ALTER SESSION SET CURRENT_SCHEMA=' . $config['schema']);
		}
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		@oci_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Dibi\ResultDriver
	{
		$this->affectedRows = null;
		$res = oci_parse($this->connection, $sql);
		if ($res) {
			@oci_execute($res, $this->autocommit ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT);
			$err = oci_error($res);
			if ($err) {
				throw self::createException($err['message'], $err['code'], $sql);

			} elseif (is_resource($res)) {
				$this->affectedRows = Dibi\Helpers::false2Null(oci_num_rows($res));
				return $this->createResultDriver($res);
			}
		} else {
			$err = oci_error($this->connection);
			throw new Dibi\DriverException($err['message'], $err['code'], $sql);
		}
		return null;
	}


	public static function createException(string $message, $code, string $sql): Dibi\DriverException
	{
		if (in_array($code, [1, 2299, 38911], true)) {
			return new Dibi\UniqueConstraintViolationException($message, $code, $sql);

		} elseif (in_array($code, [1400], true)) {
			return new Dibi\NotNullConstraintViolationException($message, $code, $sql);

		} elseif (in_array($code, [2266, 2291, 2292], true)) {
			return new Dibi\ForeignKeyConstraintViolationException($message, $code, $sql);

		} else {
			return new Dibi\DriverException($message, $code, $sql);
		}
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
		$row = $this->query("SELECT $sequence.CURRVAL AS ID FROM DUAL")->fetch(true);
		return isset($row['ID']) ? (int) $row['ID'] : null;
	}


	/**
	 * Begins a transaction (if supported).
	 */
	public function begin(string $savepoint = null): void
	{
		$this->autocommit = false;
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(string $savepoint = null): void
	{
		if (!oci_commit($this->connection)) {
			$err = oci_error($this->connection);
			throw new Dibi\DriverException($err['message'], $err['code']);
		}
		$this->autocommit = true;
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(string $savepoint = null): void
	{
		if (!oci_rollback($this->connection)) {
			$err = oci_error($this->connection);
			throw new Dibi\DriverException($err['message'], $err['code']);
		}
		$this->autocommit = true;
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
		return "'" . str_replace("'", "''", $value) . "'"; // TODO: not tested
	}


	public function escapeBinary(string $value): string
	{
		return "'" . str_replace("'", "''", $value) . "'"; // TODO: not tested
	}


	public function escapeIdentifier(string $value): string
	{
		// @see http://download.oracle.com/docs/cd/B10500_01/server.920/a96540/sql_elements9a.htm
		return '"' . str_replace('"', '""', $value) . '"';
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
		return $this->nativeDate
			? "to_date('" . $value->format('Y-m-d') . "', 'YYYY-mm-dd')"
			: $value->format('U');
	}


	/**
	 * @param  \DateTimeInterface|string|int
	 */
	public function escapeDateTime($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $this->nativeDate
			? "to_date('" . $value->format('Y-m-d G:i:s') . "', 'YYYY-mm-dd hh24:mi:ss')"
			: $value->format('U');
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function escapeLike(string $value, int $pos): string
	{
		$value = addcslashes(str_replace('\\', '\\\\', $value), "\x00\\%_");
		$value = str_replace("'", "''", $value);
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
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($offset) {
			// see http://www.oracle.com/technology/oramag/oracle/06-sep/o56asktom.html
			$sql = 'SELECT * FROM (SELECT t.*, ROWNUM AS "__rnum" FROM (' . $sql . ') t '
				. ($limit !== null ? 'WHERE ROWNUM <= ' . ($offset + $limit) : '')
				. ') WHERE "__rnum" > ' . $offset;

		} elseif ($limit !== null) {
			$sql = 'SELECT * FROM (' . $sql . ') WHERE ROWNUM <= ' . $limit;
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
		throw new Dibi\NotSupportedException('Row count is not available for unbuffered queries.');
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		return Dibi\Helpers::false2Null(oci_fetch_array($this->resultSet, ($assoc ? OCI_ASSOC : OCI_NUM) | OCI_RETURN_NULLS));
	}


	/**
	 * Moves cursor position without fetching row.
	 */
	public function seek(int $row): bool
	{
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		oci_free_statement($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = oci_num_fields($this->resultSet);
		$columns = [];
		for ($i = 1; $i <= $count; $i++) {
			$type = oci_field_type($this->resultSet, $i);
			$columns[] = [
				'name' => oci_field_name($this->resultSet, $i),
				'table' => null,
				'fullname' => oci_field_name($this->resultSet, $i),
				'nativetype' => $type === 'NUMBER' && oci_field_scale($this->resultSet, $i) === 0 ? 'INTEGER' : $type,
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
		$res = $this->query('SELECT * FROM cat');
		$tables = [];
		while ($row = $res->fetch(false)) {
			if ($row[1] === 'TABLE' || $row[1] === 'VIEW') {
				$tables[] = [
					'name' => $row[0],
					'view' => $row[1] === 'VIEW',
				];
			}
		}
		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$res = $this->query('SELECT * FROM "ALL_TAB_COLUMNS" WHERE "TABLE_NAME" = ' . $this->escapeText($table));
		$columns = [];
		while ($row = $res->fetch(true)) {
			$columns[] = [
				'table' => $row['TABLE_NAME'],
				'name' => $row['COLUMN_NAME'],
				'nativetype' => $row['DATA_TYPE'],
				'size' => $row['DATA_LENGTH'] ?? null,
				'nullable' => $row['NULLABLE'] === 'Y',
				'default' => $row['DATA_DEFAULT'],
				'vendor' => $row,
			];
		}
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
