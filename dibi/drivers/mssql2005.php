<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 *
 * Copyright (c) 2005, 2010 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license", and/or
 * GPL license. For more information please see http://dibiphp.com
 * @package    dibi\drivers
 */


/**
 * The dibi driver for MS SQL Driver 2005 database.
 *
 * Driver options:
 *   - host => the MS SQL server host name. It can also include a port number (hostname:port)
 *   - username (or user)
 *   - password (or pass)
 *   - database => the database name to select
 *   - options (array) => connection options {@link http://msdn.microsoft.com/en-us/library/cc296161(SQL.90).aspx}
 *   - charset => character encoding to set (default is UTF-8)
 *   - resource (resource) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see DibiConnection options
 *
 * @author     David Grudl
 * @package    dibi\drivers
 */
class DibiMsSql2005Driver extends DibiObject implements IDibiDriver, IDibiResultDriver
{
	/** @var resource  Connection resource */
	private $connection;

	/** @var resource  Resultset resource */
	private $resultSet;

	/** @var int|FALSE  Affected rows */
	private $affectedRows = FALSE;



	/**
	 * @throws DibiException
	 */
	public function __construct()
	{
		if (!extension_loaded('sqlsrv')) {
			throw new DibiDriverException("PHP extension 'sqlsrv' is not loaded.");
		}
	}



	/**
	 * Connects to a database.
	 * @return void
	 * @throws DibiException
	 */
	public function connect(array &$config)
	{
		DibiConnection::alias($config, 'options|UID', 'username');
		DibiConnection::alias($config, 'options|PWD', 'password');
		DibiConnection::alias($config, 'options|Database', 'database');

		if (isset($config['resource'])) {
			$this->connection = $config['resource'];

		} else {
			$this->connection = sqlsrv_connect($config['host'], (array) $config['options']);
		}

		if (!is_resource($this->connection)) {
			$info = sqlsrv_errors();
			throw new DibiDriverException($info[0]['message'], $info[0]['code']);
		}
	}



	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		sqlsrv_close($this->connection);
	}



	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return IDibiResultDriver|NULL
	 * @throws DibiDriverException
	 */
	public function query($sql)
	{
		$this->affectedRows = FALSE;
		$res = sqlsrv_query($this->connection, $sql);

		if ($res === FALSE) {
			$info = sqlsrv_errors();
			throw new DibiDriverException($info[0]['message'], $info[0]['code'], $sql);

		} elseif (is_resource($res)) {
			$this->affectedRows = sqlsrv_rows_affected($res);
			return $this->createResultDriver($res);
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
		$res = sqlsrv_query($this->connection, 'SELECT @@IDENTITY');
		if (is_resource($res)) {
			$row = sqlsrv_fetch_array($res, SQLSRV_FETCH_NUMERIC);
			return $row[0];
		}
		return FALSE;
	}



	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function begin($savepoint = NULL)
	{
		$this->query('BEGIN TRANSACTION');
	}



	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function commit($savepoint = NULL)
	{
		$this->query('COMMIT');
	}



	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function rollback($savepoint = NULL)
	{
		$this->query('ROLLBACK');
	}



	/**
	 * Returns the connection resource.
	 * @return mixed
	 */
	public function getResource()
	{
		return $this->connection;
	}



	/**
	 * Returns the connection reflector.
	 * @return IDibiReflector
	 */
	public function getReflector()
	{
		throw new NotSupportedException;
	}



	/**
	 * Result set driver factory.
	 * @param  resource
	 * @return IDibiResultDriver
	 */
	public function createResultDriver($resource)
	{
		$res = clone $this;
		$res->resultSet = $resource;
		return $res;
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
			// @see http://msdn.microsoft.com/en-us/library/ms176027.aspx
			return '[' . str_replace(array('[', ']'), array('[[', ']]'), $value) . ']';

		case dibi::BOOL:
			return $value ? 1 : 0;

		case dibi::DATE:
			return $value instanceof DateTime ? $value->format("'Y-m-d'") : date("'Y-m-d'", $value);

		case dibi::DATETIME:
			return $value instanceof DateTime ? $value->format("'Y-m-d H:i:s'") : date("'Y-m-d H:i:s'", $value);

		default:
			throw new InvalidArgumentException('Unsupported type.');
		}
	}



	/**
	 * Encodes string for use in a LIKE statement.
	 * @param  string
	 * @param  int
	 * @return string
	 */
	public function escapeLike($value, $pos)
	{
		$value = strtr($value, array("'" => "''", '%' => '[%]', '_' => '[_]', '[' => '[[]'));
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");
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

		if ($offset) {
			throw new NotImplementedException('Offset is not implemented.');
		}
	}



	/********************* result set ****************d*g**/



	/**
	 * Automatically frees the resources allocated for this result set.
	 * @return void
	 */
	public function __destruct()
	{
		$this->resultSet && @$this->free();
	}



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
	 */
	public function fetch($assoc)
	{
		return sqlsrv_fetch_array($this->resultSet, $assoc ? SQLSRV_FETCH_ASSOC : SQLSRV_FETCH_NUMERIC);
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
		sqlsrv_free_stmt($this->resultSet);
		$this->resultSet = NULL;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getResultColumns()
	{
		$count = sqlsrv_num_fields($this->resultSet);
		$columns = array();
		for ($i = 0; $i < $count; $i++) {
			$row = (array) sqlsrv_field_metadata($this->resultSet, $i);
			$columns[] = array(
				'name' => $row['Name'],
				'fullname' => $row['Name'],
				'nativetype' => $row['Type'],
			);
		}
		return $columns;
	}



	/**
	 * Returns the result set resource.
	 * @return mixed
	 */
	public function getResultResource()
	{
		return $this->resultSet;
	}

}
