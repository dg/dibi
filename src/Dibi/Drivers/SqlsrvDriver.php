<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;
use Dibi\Connection;
use Dibi\Helpers;


/**
 * The dibi driver for Microsoft SQL Server and SQL Azure databases.
 *
 * Driver options:
 *   - host => the MS SQL server host name. It can also include a port number (hostname:port)
 *   - username (or user)
 *   - password (or pass)
 *   - database => the database name to select
 *   - options (array) => connection options {@link https://msdn.microsoft.com/en-us/library/cc296161(SQL.90).aspx}
 *   - charset => character encoding to set (default is UTF-8)
 *   - resource (resource) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class SqlsrvDriver implements Dibi\Driver, Dibi\ResultDriver
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

	/** @var string */
	private $version = '';


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('sqlsrv')) {
			throw new Dibi\NotSupportedException("PHP extension 'sqlsrv' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config): void
	{
		Helpers::alias($config, 'options|UID', 'username');
		Helpers::alias($config, 'options|PWD', 'password');
		Helpers::alias($config, 'options|Database', 'database');
		Helpers::alias($config, 'options|CharacterSet', 'charset');

		if (isset($config['resource'])) {
			$this->connection = $config['resource'];

		} else {
			$options = $config['options'];

			// Default values
			$options['CharacterSet'] = $options['CharacterSet'] ?? 'UTF-8';
			$options['PWD'] = (string) $options['PWD'];
			$options['UID'] = (string) $options['UID'];
			$options['Database'] = (string) $options['Database'];

			$this->connection = sqlsrv_connect($config['host'], $options);
		}

		if (!is_resource($this->connection)) {
			$info = sqlsrv_errors();
			throw new Dibi\DriverException($info[0]['message'], $info[0]['code']);
		}
		$this->version = sqlsrv_server_info($this->connection)['SQLServerVersion'];
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		@sqlsrv_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Dibi\ResultDriver
	{
		$this->affectedRows = null;
		$res = sqlsrv_query($this->connection, $sql);

		if ($res === false) {
			$info = sqlsrv_errors();
			throw new Dibi\DriverException($info[0]['message'], $info[0]['code'], $sql);

		} elseif (is_resource($res)) {
			$this->affectedRows = Helpers::false2Null(sqlsrv_rows_affected($res));
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
		$res = sqlsrv_query($this->connection, 'SELECT SCOPE_IDENTITY()');
		if (is_resource($res)) {
			$row = sqlsrv_fetch_array($res, SQLSRV_FETCH_NUMERIC);
			return Dibi\Helpers::intVal($row[0]);
		}
		return null;
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(string $savepoint = null): void
	{
		sqlsrv_begin_transaction($this->connection);
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(string $savepoint = null): void
	{
		sqlsrv_commit($this->connection);
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(string $savepoint = null): void
	{
		sqlsrv_rollback($this->connection);
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
		return new SqlsrvReflector($this);
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
		// @see https://msdn.microsoft.com/en-us/library/ms176027.aspx
		return '[' . str_replace(']', ']]', $value) . ']';
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
		return $value->format("'Y-m-d'");
	}


	/**
	 * @param  \DateTimeInterface|string|int
	 */
	public function escapeDateTime($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format("'Y-m-d H:i:s.u'");
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
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif (version_compare($this->version, '11', '<')) { // 11 == SQL Server 2012
			if ($offset) {
				throw new Dibi\NotSupportedException('Offset is not supported by this database.');

			} elseif ($limit !== null) {
				$sql = sprintf('SELECT TOP (%d) * FROM (%s) t', $limit, $sql);
			}

		} elseif ($limit !== null) {
			// requires ORDER BY, see https://technet.microsoft.com/en-us/library/gg699618(v=sql.110).aspx
			$sql = sprintf('%s OFFSET %d ROWS FETCH NEXT %d ROWS ONLY', rtrim($sql), $offset, $limit);
		} elseif ($offset) {
			// requires ORDER BY, see https://technet.microsoft.com/en-us/library/gg699618(v=sql.110).aspx
			$sql = sprintf('%s OFFSET %d ROWS', rtrim($sql), $offset);
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
		return sqlsrv_fetch_array($this->resultSet, $assoc ? SQLSRV_FETCH_ASSOC : SQLSRV_FETCH_NUMERIC);
	}


	/**
	 * Moves cursor position without fetching row.
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
		sqlsrv_free_stmt($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$columns = [];
		foreach ((array) sqlsrv_field_metadata($this->resultSet) as $fieldMetadata) {
			$columns[] = [
				'name' => $fieldMetadata['Name'],
				'fullname' => $fieldMetadata['Name'],
				'nativetype' => $fieldMetadata['Type'],
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
}
