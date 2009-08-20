<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com
 *
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */


/**
 * The dibi driver for PDO.
 *
 * Connection options:
 *   - 'dsn' - driver specific DSN
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'options' - driver specific options array
 *   - 'resource' - PDO object (optional)
 *   - 'lazy' - if TRUE, connection will be established only when required
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiPdoDriver extends DibiObject implements IDibiDriver
{
	/** @var PDO  Connection resource */
	private $connection;

	/** @var PDOStatement  Resultset resource */
	private $resultSet;

	/** @var int|FALSE  Affected rows */
	private $affectedRows = FALSE;



	/**
	 * @throws DibiException
	 */
	public function __construct()
	{
		if (!extension_loaded('pdo')) {
			throw new DibiDriverException("PHP extension 'pdo' is not loaded.");
		}
	}



	/**
	 * Connects to a database.
	 * @return void
	 * @throws DibiException
	 */
	public function connect(array &$config)
	{
		DibiConnection::alias($config, 'dsn');
		DibiConnection::alias($config, 'resource', 'pdo');
		DibiConnection::alias($config, 'options');

		if ($config['resource'] instanceof PDO) {
			$this->connection = $config['resource'];

		} else try {
			$this->connection = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);

		} catch (PDOException $e) {
			throw new DibiDriverException($e->getMessage(), $e->getCode());
		}

		if (!$this->connection) {
			throw new DibiDriverException('Connecting error.');
		}
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
	 * @return IDibiDriver|NULL
	 * @throws DibiDriverException
	 */
	public function query($sql)
	{
		// must detect if SQL returns result set or num of affected rows
		$cmd = strtoupper(substr(ltrim($sql), 0, 6));
		$list = array('UPDATE'=>1, 'DELETE'=>1, 'INSERT'=>1, 'REPLAC'=>1);

		if (isset($list[$cmd])) {
			$this->resultSet = NULL;
			$this->affectedRows = $this->connection->exec($sql);

			if ($this->affectedRows === FALSE) {
				$err = $this->connection->errorInfo();
				throw new DibiDriverException("SQLSTATE[$err[0]]: $err[2]", $err[1], $sql);
			}

			return NULL;

		} else {
			$this->resultSet = $this->connection->query($sql);
			$this->affectedRows = FALSE;

			if ($this->resultSet === FALSE) {
				$err = $this->connection->errorInfo();
				throw new DibiDriverException("SQLSTATE[$err[0]]: $err[2]", $err[1], $sql);
			}

			return clone $this;
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
	 * @throws DibiDriverException
	 */
	public function begin($savepoint = NULL)
	{
		if (!$this->connection->beginTransaction()) {
			$err = $this->connection->errorInfo();
			throw new DibiDriverException("SQLSTATE[$err[0]]: $err[2]", $err[1]);
		}
	}



	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function commit($savepoint = NULL)
	{
		if (!$this->connection->commit()) {
			$err = $this->connection->errorInfo();
			throw new DibiDriverException("SQLSTATE[$err[0]]: $err[2]", $err[1]);
		}
	}



	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function rollback($savepoint = NULL)
	{
		if (!$this->connection->rollBack()) {
			$err = $this->connection->errorInfo();
			throw new DibiDriverException("SQLSTATE[$err[0]]: $err[2]", $err[1]);
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



	/********************* SQL ****************d*g**/



	/**
	 * Encodes data for use in a SQL statement.
	 * @param  mixed     value
	 * @param  string    type (dibi::TEXT, dibi::BOOL, ...)
	 * @return string    encoded value
	 * @throws InvalidArgumentException
	 */
	public function escape($value, $type)
	{
		switch ($type) {
		case dibi::TEXT:
			return $this->connection->quote($value, PDO::PARAM_STR);

		case dibi::BINARY:
			return $this->connection->quote($value, PDO::PARAM_LOB);

		case dibi::IDENTIFIER:
			switch ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)) {
			case 'mysql':
				$value = str_replace('`', '``', $value);
				return '`' . str_replace('.', '`.`', $value) . '`';

			case 'pgsql':
				$a = strrpos($value, '.');
				if ($a === FALSE) {
					return '"' . str_replace('"', '""', $value) . '"';
				} else {
					return substr($value, 0, $a) . '."' . str_replace('"', '""', substr($value, $a + 1)) . '"';
				}

			case 'sqlite':
			case 'sqlite2':
				$value = strtr($value, '[]', '  ');
			case 'odbc':
			case 'oci': // TODO: not tested
			case 'mssql':
				$value = str_replace(array('[', ']'), array('[[', ']]'), $value);
				return '[' . str_replace('.', '].[', $value) . ']';

			default:
				return $value;
			}

		case dibi::BOOL:
			return $this->connection->quote($value, PDO::PARAM_BOOL);

		case dibi::DATE:
			return $value instanceof DateTime ? $value->format("'Y-m-d'") : date("'Y-m-d'", $value);

		case dibi::DATETIME:
			return $value instanceof DateTime ? $value->format("'Y-m-d H:i:s'") : date("'Y-m-d H:i:s'", $value);

		default:
			throw new InvalidArgumentException('Unsupported type.');
		}
	}



	/**
	 * Decodes data from result set.
	 * @param  string    value
	 * @param  string    type (dibi::BINARY)
	 * @return string    decoded value
	 * @throws InvalidArgumentException
	 */
	public function unescape($value, $type)
	{
		if ($type === dibi::BINARY) {
			return $value;
		}
		throw new InvalidArgumentException('Unsupported type.');
	}



	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 * @param  string &$sql  The SQL query that will be modified.
	 * @param  int $limit
	 * @param  int $offset
	 * @return void
	 */
	public function applyLimit(&$sql, $limit, $offset)
	{
		if ($limit < 0 && $offset < 1) return;

		switch ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)) {
		case 'mysql':
			$sql .= ' LIMIT ' . ($limit < 0 ? '18446744073709551615' : (int) $limit)
				. ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
			break;

		case 'pgsql':
			if ($limit >= 0) $sql .= ' LIMIT ' . (int) $limit;
			if ($offset > 0) $sql .= ' OFFSET ' . (int) $offset;
			break;

		case 'sqlite':
		case 'sqlite2':
			$sql .= ' LIMIT ' . $limit . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
			break;

		case 'oci':
			if ($offset > 0) {
				$sql = 'SELECT * FROM (SELECT t.*, ROWNUM AS "__rnum" FROM (' . $sql . ') t ' . ($limit >= 0 ? 'WHERE ROWNUM <= ' . ((int) $offset + (int) $limit) : '') . ') WHERE "__rnum" > '. (int) $offset;
			} elseif ($limit >= 0) {
				$sql = 'SELECT * FROM (' . $sql . ') WHERE ROWNUM <= ' . (int) $limit;
			}
			break;

		case 'odbc':
		case 'mssql':
			if ($offset < 1) {
				$sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ')';
				break;
			}
			// intentionally break omitted

		default:
			throw new NotSupportedException('PDO or driver does not support applying limit or offset.');
		}
	}



	/********************* result set ****************d*g**/



	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	public function getRowCount()
	{
		throw new NotSupportedException('Row count is not available for unbuffered queries.');
	}



	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 * @internal
	 */
	public function fetch($assoc)
	{
		return $this->resultSet->fetch($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
	}



	/**
	 * Moves cursor position without fetching row.
	 * @param  int      the 0-based cursor pos to seek to
	 * @return boolean  TRUE on success, FALSE if unable to seek to specified record
	 */
	public function seek($row)
	{
		throw new NotSupportedException('Cannot seek an unbuffered result set.');
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
	 * @throws DibiException
	 */
	public function getColumnsMeta()
	{
		$count = $this->resultSet->columnCount();
		$res = array();
		for ($i = 0; $i < $count; $i++) {
			$row = @$this->resultSet->getColumnMeta($i); // intentionally @
			if ($row === FALSE) {
				throw new DibiDriverException('Driver does not support meta data.');
			}
			$res[] = array(
				'name' => $row['name'],
				'table' => $row['table'],
				'nativetype' => $row['native_type'],
				'fullname' => $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'],
				'vendor' => $row,
			);
		}
		return $res;
	}



	/**
	 * Returns the result set resource.
	 * @return PDOStatement
	 */
	public function getResultResource()
	{
		return $this->resultSet;
	}



	/********************* reflection ****************d*g**/



	/**
	 * Returns list of tables.
	 * @return array
	 */
	public function getTables()
	{
		throw new NotImplementedException;
	}



	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		throw new NotImplementedException;
	}



	/**
	 * Returns metadata for all indexes in a table.
	 * @param  string
	 * @return array
	 */
	public function getIndexes($table)
	{
		throw new NotImplementedException;
	}



	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	public function getForeignKeys($table)
	{
		throw new NotImplementedException;
	}

}
