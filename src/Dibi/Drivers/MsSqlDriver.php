<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi driver for MS SQL database.
 *
 * Driver options:
 *   - host => the MS SQL server host name. It can also include a port number (hostname:port)
 *   - username (or user)
 *   - password (or pass)
 *   - database => the database name to select
 *   - persistent (bool) => try to find a persistent link?
 *   - resource (resource) => existing connection resource
 */
class MsSqlDriver implements Dibi\Driver
{
	use Dibi\Strict;

	/** @var resource|null */
	private $connection;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('mssql')) {
			throw new Dibi\NotSupportedException("PHP extension 'mssql' is not loaded.");
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
		} elseif (empty($config['persistent'])) {
			$this->connection = @mssql_connect($config['host'], $config['username'], $config['password'], true); // intentionally @
		} else {
			$this->connection = @mssql_pconnect($config['host'], $config['username'], $config['password']); // intentionally @
		}

		if (!is_resource($this->connection)) {
			throw new Dibi\DriverException("Can't connect to DB.");
		}

		if (isset($config['database']) && !@mssql_select_db($this->escapeIdentifier($config['database']), $this->connection)) { // intentionally @
			throw new Dibi\DriverException("Can't select DB '$config[database]'.");
		}
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		@mssql_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Dibi\ResultDriver
	{
		$res = @mssql_query($sql, $this->connection); // intentionally @

		if ($res === false) {
			throw new Dibi\DriverException(mssql_get_last_message(), 0, $sql);

		} elseif (is_resource($res)) {
			return $this->createResultDriver($res);
		}
		return null;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 */
	public function getAffectedRows(): ?int
	{
		return Dibi\Helpers::false2Null(mssql_rows_affected($this->connection));
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 */
	public function getInsertId(?string $sequence): ?int
	{
		$res = mssql_query('SELECT @@IDENTITY', $this->connection);
		if (is_resource($res)) {
			$row = mssql_fetch_row($res);
			return $row[0];
		}
		return null;
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(string $savepoint = null): void
	{
		$this->query('BEGIN TRANSACTION');
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(string $savepoint = null): void
	{
		$this->query('COMMIT');
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(string $savepoint = null): void
	{
		$this->query('ROLLBACK');
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
		return new MsSqlReflector($this);
	}


	/**
	 * Result set driver factory.
	 * @param  resource  $resource
	 */
	public function createResultDriver($resource): MsSqlResult
	{
		return new MsSqlResult($resource);
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
		return '[' . str_replace(['[', ']'], ['[[', ']]'], $value) . ']';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? '1' : '0';
	}


	/**
	 * @param  \DateTimeInterface|string|int  $value
	 */
	public function escapeDate($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format("'Y-m-d'");
	}


	/**
	 * @param  \DateTimeInterface|string|int  $value
	 */
	public function escapeDateTime($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return 'CONVERT(DATETIME2(7), ' . $value->format("'Y-m-d H:i:s.u'") . ')';
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
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
	{
		if ($offset) {
			throw new Dibi\NotSupportedException('Offset is not supported by this database.');

		} elseif ($limit < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($limit !== null) {
			$sql = 'SELECT TOP ' . $limit . ' * FROM (' . $sql . ') t';
		}
	}
}
