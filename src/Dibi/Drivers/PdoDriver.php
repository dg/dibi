<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;
use Dibi\Helpers;
use PDO;


/**
 * The dibi driver for PDO.
 *
 * Driver options:
 *   - dsn => driver specific DSN
 *   - username (or user)
 *   - password (or pass)
 *   - options (array) => driver specific options {@see PDO::__construct}
 *   - resource (PDO) => existing connection
 *   - version
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class PdoDriver implements Dibi\Driver, Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var PDO  Connection resource */
	private $connection;

	/** @var \PDOStatement|null  Resultset resource */
	private $resultSet;

	/** @var int|null  Affected rows */
	private $affectedRows;

	/** @var string */
	private $driverName;

	/** @var string */
	private $serverVersion = '';


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('pdo')) {
			throw new Dibi\NotSupportedException("PHP extension 'pdo' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config): void
	{
		$foo = &$config['dsn'];
		$foo = &$config['options'];
		Helpers::alias($config, 'resource', 'pdo');

		if ($config['resource'] instanceof PDO) {
			$this->connection = $config['resource'];
			unset($config['resource'], $config['pdo']);
		} else {
			try {
				$this->connection = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
			} catch (\PDOException $e) {
				if ($e->getMessage() === 'could not find driver') {
					throw new Dibi\NotSupportedException('PHP extension for PDO is not loaded.');
				}
				throw new Dibi\DriverException($e->getMessage(), $e->getCode());
			}
		}

		$this->driverName = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
		$this->serverVersion = (string) ($config['version'] ?? @$this->connection->getAttribute(PDO::ATTR_SERVER_VERSION)); // @ - may be not supported
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		$this->connection = null;
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Dibi\ResultDriver
	{
		// must detect if SQL returns result set or num of affected rows
		$cmd = strtoupper(substr(ltrim($sql), 0, 6));
		static $list = ['UPDATE' => 1, 'DELETE' => 1, 'INSERT' => 1, 'REPLAC' => 1];
		$this->affectedRows = null;

		if (isset($list[$cmd])) {
			$this->affectedRows = Helpers::false2Null($this->connection->exec($sql));
			if ($this->affectedRows !== null) {
				return null;
			}
		} else {
			$res = $this->connection->query($sql);
			if ($res) {
				return $this->createResultDriver($res);
			}
		}

		[$sqlState, $code, $message] = $this->connection->errorInfo();
		$message = "SQLSTATE[$sqlState]: $message";
		switch ($this->driverName) {
			case 'mysql':
				throw MySqliDriver::createException($message, $code, $sql);

			case 'oci':
				throw OracleDriver::createException($message, $code, $sql);

			case 'pgsql':
				throw PostgreDriver::createException($message, $sqlState, $sql);

			case 'sqlite':
				throw Sqlite3Driver::createException($message, $code, $sql);

			default:
				throw new Dibi\DriverException($message, $code, $sql);
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
		return Helpers::false2Null($this->connection->lastInsertId());
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(string $savepoint = null): void
	{
		if (!$this->connection->beginTransaction()) {
			$err = $this->connection->errorInfo();
			throw new Dibi\DriverException("SQLSTATE[$err[0]]: $err[2]", $err[1]);
		}
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(string $savepoint = null): void
	{
		if (!$this->connection->commit()) {
			$err = $this->connection->errorInfo();
			throw new Dibi\DriverException("SQLSTATE[$err[0]]: $err[2]", $err[1]);
		}
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(string $savepoint = null): void
	{
		if (!$this->connection->rollBack()) {
			$err = $this->connection->errorInfo();
			throw new Dibi\DriverException("SQLSTATE[$err[0]]: $err[2]", $err[1]);
		}
	}


	/**
	 * Returns the connection resource.
	 */
	public function getResource(): PDO
	{
		return $this->connection;
	}


	/**
	 * Returns the connection reflector.
	 */
	public function getReflector(): Dibi\Reflector
	{
		switch ($this->driverName) {
			case 'mysql':
				return new MySqlReflector($this);

			case 'sqlite':
				return new SqliteReflector($this);

			default:
				throw new Dibi\NotSupportedException;
		}
	}


	/**
	 * Result set driver factory.
	 */
	public function createResultDriver(\PDOStatement $resource): Dibi\ResultDriver
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
		if ($this->driverName === 'odbc') {
			return "'" . str_replace("'", "''", $value) . "'";
		} else {
			return $this->connection->quote($value, PDO::PARAM_STR);
		}
	}


	public function escapeBinary(string $value): string
	{
		if ($this->driverName === 'odbc') {
			return "'" . str_replace("'", "''", $value) . "'";
		} else {
			return $this->connection->quote($value, PDO::PARAM_LOB);
		}
	}


	public function escapeIdentifier(string $value): string
	{
		switch ($this->driverName) {
			case 'mysql':
				return '`' . str_replace('`', '``', $value) . '`';

			case 'oci':
			case 'pgsql':
				return '"' . str_replace('"', '""', $value) . '"';

			case 'sqlite':
				return '[' . strtr($value, '[]', '  ') . ']';

			case 'odbc':
			case 'mssql':
				return '[' . str_replace(['[', ']'], ['[[', ']]'], $value) . ']';

			case 'dblib':
			case 'sqlsrv':
				return '[' . str_replace(']', ']]', $value) . ']';

			default:
				return $value;
		}
	}


	public function escapeBool(bool $value): string
	{
		if ($this->driverName === 'pgsql') {
			return $value ? 'TRUE' : 'FALSE';
		} else {
			return $value ? '1' : '0';
		}
	}


	/**
	 * @param  \DateTimeInterface|string|int
	 */
	public function escapeDate($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format($this->driverName === 'odbc' ? '#m/d/Y#' : "'Y-m-d'");
	}


	/**
	 * @param  \DateTimeInterface|string|int
	 */
	public function escapeDateTime($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		switch ($this->driverName) {
			case 'odbc':
				return $value->format('#m/d/Y H:i:s.u#');
			case 'mssql':
			case 'dblib':
			case 'sqlsrv':
				return 'CONVERT(DATETIME2(7), ' . $value->format("'Y-m-d H:i:s.u'") . ')';
			default:
				return $value->format("'Y-m-d H:i:s.u'");
		}
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function escapeLike(string $value, int $pos): string
	{
		switch ($this->driverName) {
			case 'mysql':
				$value = addcslashes(str_replace('\\', '\\\\', $value), "\x00\n\r\\'%_");
				return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");

			case 'oci':
				$value = addcslashes(str_replace('\\', '\\\\', $value), "\x00\\%_");
				$value = str_replace("'", "''", $value);
				return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");

			case 'pgsql':
				$bs = substr($this->connection->quote('\\', PDO::PARAM_STR), 1, -1); // standard_conforming_strings = on/off
				$value = substr($this->connection->quote($value, PDO::PARAM_STR), 1, -1);
				$value = strtr($value, ['%' => $bs . '%', '_' => $bs . '_', '\\' => '\\\\']);
				return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");

			case 'sqlite':
				$value = addcslashes(substr($this->connection->quote($value, PDO::PARAM_STR), 1, -1), '%_\\');
				return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'") . " ESCAPE '\\'";

			case 'odbc':
			case 'mssql':
			case 'dblib':
			case 'sqlsrv':
				$value = strtr($value, ["'" => "''", '%' => '[%]', '_' => '[_]', '[' => '[[]']);
				return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");

			default:
				throw new Dibi\NotImplementedException;
		}
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
		}

		switch ($this->driverName) {
			case 'mysql':
				if ($limit !== null || $offset) {
					// see http://dev.mysql.com/doc/refman/5.0/en/select.html
					$sql .= ' LIMIT ' . ($limit === null ? '18446744073709551615' : $limit)
						. ($offset ? ' OFFSET ' . $offset : '');
				}
				break;

			case 'pgsql':
				if ($limit !== null) {
					$sql .= ' LIMIT ' . $limit;
				}
				if ($offset) {
					$sql .= ' OFFSET ' . $offset;
				}
				break;

			case 'sqlite':
				if ($limit !== null || $offset) {
					$sql .= ' LIMIT ' . ($limit === null ? '-1' : $limit)
						. ($offset ? ' OFFSET ' . $offset : '');
				}
				break;

			case 'oci':
				if ($offset) {
					// see http://www.oracle.com/technology/oramag/oracle/06-sep/o56asktom.html
					$sql = 'SELECT * FROM (SELECT t.*, ROWNUM AS "__rnum" FROM (' . $sql . ') t '
						. ($limit !== null ? 'WHERE ROWNUM <= ' . ($offset + $limit) : '')
						. ') WHERE "__rnum" > ' . $offset;

				} elseif ($limit !== null) {
					$sql = 'SELECT * FROM (' . $sql . ') WHERE ROWNUM <= ' . $limit;
				}
				break;

			case 'mssql':
			case 'sqlsrv':
			case 'dblib':
				if (version_compare($this->serverVersion, '11.0') >= 0) { // 11 == SQL Server 2012
					// requires ORDER BY, see https://technet.microsoft.com/en-us/library/gg699618(v=sql.110).aspx
					if ($limit !== null) {
						$sql = sprintf('%s OFFSET %d ROWS FETCH NEXT %d ROWS ONLY', rtrim($sql), $offset, $limit);
					} elseif ($offset) {
						$sql = sprintf('%s OFFSET %d ROWS', rtrim($sql), $offset);
					}
					break;
				}
				// break omitted

			case 'odbc':
				if ($offset) {
					throw new Dibi\NotSupportedException('Offset is not supported by this database.');

				} elseif ($limit !== null) {
					$sql = 'SELECT TOP ' . $limit . ' * FROM (' . $sql . ') t';
					break;
				}
				// break omitted

			default:
				throw new Dibi\NotSupportedException('PDO or driver does not support applying limit or offset.');
		}
	}


	/********************* result set ****************d*g**/


	/**
	 * Returns the number of rows in a result set.
	 */
	public function getRowCount(): int
	{
		return $this->resultSet->rowCount();
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		return Helpers::false2Null($this->resultSet->fetch($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM));
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
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 * @throws Dibi\Exception
	 */
	public function getResultColumns(): array
	{
		$count = $this->resultSet->columnCount();
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = @$this->resultSet->getColumnMeta($i); // intentionally @
			if ($row === false) {
				throw new Dibi\NotSupportedException('Driver does not support meta data.');
			}
			$row = $row + [
				'table' => null,
				'native_type' => 'VAR_STRING',
			];

			$columns[] = [
				'name' => $row['name'],
				'table' => $row['table'],
				'nativetype' => $row['native_type'],
				'type' => $row['native_type'] === 'TIME' && $this->driverName === 'mysql' ? Dibi\Type::TIME_INTERVAL : null,
				'fullname' => $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'],
				'vendor' => $row,
			];
		}
		return $columns;
	}


	/**
	 * Returns the result set resource.
	 */
	public function getResultResource(): ?\PDOStatement
	{
		return $this->resultSet;
	}
}
