<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;

use Dibi;
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

	/** @var \PDOStatement  Resultset resource */
	private $resultSet;

	/** @var int|FALSE  Affected rows */
	private $affectedRows = FALSE;

	/** @var string */
	private $driverName;

	/** @var string */
	private $serverVersion;


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
	 * @return void
	 * @throws Dibi\Exception
	 */
	public function connect(array & $config)
	{
		$foo = & $config['dsn'];
		$foo = & $config['options'];
		Dibi\Helpers::alias($config, 'resource', 'pdo');

		if ($config['resource'] instanceof PDO) {
			$this->connection = $config['resource'];

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
		$this->serverVersion = isset($config['version'])
			? $config['version']
			: @$this->connection->getAttribute(PDO::ATTR_SERVER_VERSION); // @ - may be not supported
	}


	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		$this->connection = NULL;
	}


	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return Dibi\ResultDriver|NULL
	 * @throws Dibi\DriverException
	 */
	public function query($sql)
	{
		// must detect if SQL returns result set or num of affected rows
		$cmd = strtoupper(substr(ltrim($sql), 0, 6));
		static $list = ['UPDATE' => 1, 'DELETE' => 1, 'INSERT' => 1, 'REPLAC' => 1];
		$this->affectedRows = FALSE;

		if (isset($list[$cmd])) {
			$this->affectedRows = $this->connection->exec($sql);
			if ($this->affectedRows !== FALSE) {
				return;
			}
		} else {
			$res = $this->connection->query($sql);
			if ($res) {
				return $this->createResultDriver($res);
			}
		}

		list($sqlState, $code, $message) = $this->connection->errorInfo();
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
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function getAffectedRows()
	{
		return $this->affectedRows;
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function getInsertId($sequence)
	{
		return $this->connection->lastInsertId();
	}


	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function begin($savepoint = NULL)
	{
		if (!$this->connection->beginTransaction()) {
			$err = $this->connection->errorInfo();
			throw new Dibi\DriverException("SQLSTATE[$err[0]]: $err[2]", $err[1]);
		}
	}


	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function commit($savepoint = NULL)
	{
		if (!$this->connection->commit()) {
			$err = $this->connection->errorInfo();
			throw new Dibi\DriverException("SQLSTATE[$err[0]]: $err[2]", $err[1]);
		}
	}


	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function rollback($savepoint = NULL)
	{
		if (!$this->connection->rollBack()) {
			$err = $this->connection->errorInfo();
			throw new Dibi\DriverException("SQLSTATE[$err[0]]: $err[2]", $err[1]);
		}
	}


	/**
	 * Returns the connection resource.
	 * @return PDO
	 */
	public function getResource()
	{
		return $this->connection;
	}


	/**
	 * Returns the connection reflector.
	 * @return Dibi\Reflector
	 */
	public function getReflector()
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
	 * @param  \PDOStatement
	 * @return Dibi\ResultDriver
	 */
	public function createResultDriver(\PDOStatement $resource)
	{
		$res = clone $this;
		$res->resultSet = $resource;
		return $res;
	}


	/********************* SQL ****************d*g**/


	/**
	 * Encodes data for use in a SQL statement.
	 * @param  mixed     value
	 * @return string    encoded value
	 */
	public function escapeText($value)
	{
		if ($this->driverName === 'odbc') {
			return "'" . str_replace("'", "''", $value) . "'";
		} else {
			return $this->connection->quote($value, PDO::PARAM_STR);
		}
	}


	public function escapeBinary($value)
	{
		if ($this->driverName === 'odbc') {
			return "'" . str_replace("'", "''", $value) . "'";
		} else {
			return $this->connection->quote($value, PDO::PARAM_LOB);
		}
	}


	public function escapeIdentifier($value)
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
				return '[' . str_replace(['[', ']'], ['[[', ']]'], $value) . ']';

			case 'dblib':
			case 'sqlsrv':
				return '[' . str_replace(']', ']]', $value) . ']';

			default:
				return $value;
		}
	}


	public function escapeBool($value)
	{
		if ($this->driverName === 'pgsql') {
			return $value ? 'TRUE' : 'FALSE';
		} else {
			return $value ? 1 : 0;
		}
	}


	public function escapeDate($value)
	{
		if (!$value instanceof \DateTime && !$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format($this->driverName === 'odbc' ? '#m/d/Y#' : "'Y-m-d'");
	}


	public function escapeDateTime($value)
	{
		if (!$value instanceof \DateTime && !$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format($this->driverName === 'odbc' ? "#m/d/Y H:i:s#" : "'Y-m-d H:i:s'");
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 * @param  string
	 * @param  int
	 * @return string
	 */
	public function escapeLike($value, $pos)
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
	 * @param  string
	 * @return string
	 */
	public function unescapeBinary($value)
	{
		return $value;
	}


	/** @deprecated */
	public function escape($value, $type)
	{
		trigger_error(__METHOD__ . '() is deprecated.', E_USER_DEPRECATED);
		return Dibi\Helpers::escape($this, $value, $type);
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 * @return void
	 */
	public function applyLimit(& $sql, $limit, $offset)
	{
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');
		}

		switch ($this->driverName) {
			case 'mysql':
				if ($limit !== NULL || $offset) {
					// see http://dev.mysql.com/doc/refman/5.0/en/select.html
					$sql .= ' LIMIT ' . ($limit === NULL ? '18446744073709551615' : (int) $limit)
						. ($offset ? ' OFFSET ' . (int) $offset : '');
				}
				break;

			case 'pgsql':
				if ($limit !== NULL) {
					$sql .= ' LIMIT ' . (int) $limit;
				}
				if ($offset) {
					$sql .= ' OFFSET ' . (int) $offset;
				}
				break;

			case 'sqlite':
				if ($limit !== NULL || $offset) {
					$sql .= ' LIMIT ' . ($limit === NULL ? '-1' : (int) $limit)
						. ($offset ? ' OFFSET ' . (int) $offset : '');
				}
				break;

			case 'oci':
				if ($offset) {
					// see http://www.oracle.com/technology/oramag/oracle/06-sep/o56asktom.html
					$sql = 'SELECT * FROM (SELECT t.*, ROWNUM AS "__rnum" FROM (' . $sql . ') t '
						. ($limit !== NULL ? 'WHERE ROWNUM <= ' . ((int) $offset + (int) $limit) : '')
						. ') WHERE "__rnum" > '. (int) $offset;

				} elseif ($limit !== NULL) {
					$sql = 'SELECT * FROM (' . $sql . ') WHERE ROWNUM <= ' . (int) $limit;
				}
				break;

			case 'sqlsrv':
			case 'dblib':
				if (version_compare($this->serverVersion, '11.0') >= 0) { // 11 == SQL Server 2012
					if ($limit !== NULL || $offset) {
						// requires ORDER BY, see https://technet.microsoft.com/en-us/library/gg699618(v=sql.110).aspx
						$sql .= ' OFFSET ' . (int) $offset . ' ROWS '
							. 'FETCH NEXT ' . (int) $limit . ' ROWS ONLY';
					}
					break;
				}
				// intentionally break omitted

			case 'odbc':
				if ($offset) {
					throw new Dibi\NotSupportedException('Offset is not supported by this database.');

				} elseif ($limit !== NULL) {
					$sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ') t';
					break;
				}
				// intentionally break omitted

			default:
				throw new Dibi\NotSupportedException('PDO or driver does not support applying limit or offset.');
		}
	}


	/********************* result set ****************d*g**/


	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	public function getRowCount()
	{
		return $this->resultSet->rowCount();
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 */
	public function fetch($assoc)
	{
		return $this->resultSet->fetch($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
	}


	/**
	 * Moves cursor position without fetching row.
	 * @param  int   the 0-based cursor pos to seek to
	 * @return bool  TRUE on success, FALSE if unable to seek to specified record
	 */
	public function seek($row)
	{
		throw new Dibi\NotSupportedException('Cannot seek an unbuffered result set.');
	}


	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	public function free()
	{
		$this->resultSet = NULL;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 * @throws Dibi\Exception
	 */
	public function getResultColumns()
	{
		$count = $this->resultSet->columnCount();
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = @$this->resultSet->getColumnMeta($i); // intentionally @
			if ($row === FALSE) {
				throw new Dibi\NotSupportedException('Driver does not support meta data.');
			}
			$row = $row + [
				'table' => NULL,
				'native_type' => 'VAR_STRING',
			];

			$columns[] = [
				'name' => $row['name'],
				'table' => $row['table'],
				'nativetype' => $row['native_type'],
				'type' => $row['native_type'] === 'TIME' && $this->driverName === 'mysql' ? Dibi\Type::TIME_INTERVAL : NULL,
				'fullname' => $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'],
				'vendor' => $row,
			];
		}
		return $columns;
	}


	/**
	 * Returns the result set resource.
	 * @return \PDOStatement
	 */
	public function getResultResource()
	{
		return $this->resultSet;
	}

}
