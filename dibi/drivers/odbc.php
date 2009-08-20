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
 * The dibi driver interacting with databases via ODBC connections.
 *
 * Connection options:
 *   - 'dsn' - driver specific DSN
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'persistent' - try to find a persistent link?
 *   - 'lazy' - if TRUE, connection will be established only when required
 *   - 'resource' - connection resource (optional)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiOdbcDriver extends DibiObject implements IDibiDriver
{
	/** @var resource  Connection resource */
	private $connection;

	/** @var resource  Resultset resource */
	private $resultSet;

	/** @var int  Cursor */
	private $row = 0;



	/**
	 * @throws DibiException
	 */
	public function __construct()
	{
		if (!extension_loaded('odbc')) {
			throw new DibiDriverException("PHP extension 'odbc' is not loaded.");
		}
	}



	/**
	 * Connects to a database.
	 * @return void
	 * @throws DibiException
	 */
	public function connect(array &$config)
	{
		if (isset($config['resource'])) {
			$this->connection = $config['resource'];
		} else {
			// default values
			if (!isset($config['username'])) $config['username'] = ini_get('odbc.default_user');
			if (!isset($config['password'])) $config['password'] = ini_get('odbc.default_pw');
			if (!isset($config['dsn'])) $config['dsn'] = ini_get('odbc.default_db');

			if (empty($config['persistent'])) {
				$this->connection = @odbc_connect($config['dsn'], $config['username'], $config['password']); // intentionally @
			} else {
				$this->connection = @odbc_pconnect($config['dsn'], $config['username'], $config['password']); // intentionally @
			}
		}

		if (!is_resource($this->connection)) {
			throw new DibiDriverException(odbc_errormsg() . ' ' . odbc_error());
		}
	}



	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		odbc_close($this->connection);
	}



	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return IDibiDriver|NULL
	 * @throws DibiDriverException
	 */
	public function query($sql)
	{
		$this->resultSet = @odbc_exec($this->connection, $sql); // intentionally @

		if ($this->resultSet === FALSE) {
			throw new DibiDriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection), 0, $sql);
		}

		return is_resource($this->resultSet) ? clone $this : NULL;
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function getAffectedRows()
	{
		return odbc_num_rows($this->resultSet);
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function getInsertId($sequence)
	{
		throw new NotSupportedException('ODBC does not support autoincrementing.');
	}



	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function begin($savepoint = NULL)
	{
		if (!odbc_autocommit($this->connection, FALSE)) {
			throw new DibiDriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
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
		if (!odbc_commit($this->connection)) {
			throw new DibiDriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}
		odbc_autocommit($this->connection, TRUE);
	}



	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function rollback($savepoint = NULL)
	{
		if (!odbc_rollback($this->connection)) {
			throw new DibiDriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}
		odbc_autocommit($this->connection, TRUE);
	}



	/**
	 * Returns the connection resource.
	 * @return mixed
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
		case dibi::BINARY:
			return "'" . str_replace("'", "''", $value) . "'";

		case dibi::IDENTIFIER:
			$value = str_replace(array('[', ']'), array('[[', ']]'), $value);
			return '[' . str_replace('.', '].[', $value) . ']';

		case dibi::BOOL:
			return $value ? 1 : 0;

		case dibi::DATE:
			return $value instanceof DateTime ? $value->format("#m/d/Y#") : date("#m/d/Y#", $value);

		case dibi::DATETIME:
			return $value instanceof DateTime ? $value->format("#m/d/Y H:i:s#") : date("#m/d/Y H:i:s#", $value);

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
		// offset support is missing
		if ($limit >= 0) {
			$sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ')';
		}

		if ($offset) throw new InvalidArgumentException('Offset is not implemented in driver odbc.');
	}



	/********************* result set ****************d*g**/



	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	public function getRowCount()
	{
		// will return -1 with many drivers :-(
		return odbc_num_rows($this->resultSet);
	}



	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 * @internal
	 */
	public function fetch($assoc)
	{
		if ($assoc) {
			return odbc_fetch_array($this->resultSet, ++$this->row);
		} else {
			$set = $this->resultSet;
			if (!odbc_fetch_row($set, ++$this->row)) return FALSE;
			$count = odbc_num_fields($set);
			$cols = array();
			for ($i = 1; $i <= $count; $i++) $cols[] = odbc_result($set, $i);
			return $cols;
		}
	}



	/**
	 * Moves cursor position without fetching row.
	 * @param  int      the 0-based cursor pos to seek to
	 * @return boolean  TRUE on success, FALSE if unable to seek to specified record
	 */
	public function seek($row)
	{
		$this->row = $row;
		return TRUE;
	}



	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	public function free()
	{
		odbc_free_result($this->resultSet);
		$this->resultSet = NULL;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getColumnsMeta()
	{
		$count = odbc_num_fields($this->resultSet);
		$res = array();
		for ($i = 1; $i <= $count; $i++) {
			$res[] = array(
				'name'      => odbc_field_name($this->resultSet, $i),
				'table'     => NULL,
				'fullname'  => odbc_field_name($this->resultSet, $i),
				'nativetype'=> odbc_field_type($this->resultSet, $i),
			);
		}
		return $res;
	}



	/**
	 * Returns the result set resource.
	 * @return mixed
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
		$result = odbc_tables($this->connection);
		$res = array();
		while ($row = odbc_fetch_array($result)) {
			if ($row['TABLE_TYPE'] === 'TABLE' || $row['TABLE_TYPE'] === 'VIEW') {
				$res[] = array(
					'name' => $row['TABLE_NAME'],
					'view' => $row['TABLE_TYPE'] === 'VIEW',
				);
			}
		}
		odbc_free_result($result);
		return $res;
	}



	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		$result = odbc_columns($this->connection);
		$res = array();
		while ($row = odbc_fetch_array($result)) {
			if ($row['TABLE_NAME'] === $table) {
				$res[] = array(
					'name' => $row['COLUMN_NAME'],
					'table' => $table,
					'nativetype' => $row['TYPE_NAME'],
					'size' => $row['COLUMN_SIZE'],
					'nullable' => (bool) $row['NULLABLE'],
					'default' => $row['COLUMN_DEF'],
				);
			}
		}
		odbc_free_result($result);
		return $res;
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
