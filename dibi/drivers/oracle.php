<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com
 *
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 * @version    $Id$
 */


/**
 * The dibi driver for Oracle database.
 *
 * Connection options:
 *   - 'database' (or 'db') - the name of the local Oracle instance or the name of the entry in tnsnames.ora
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'charset' - character encoding to set
 *   - 'lazy' - if TRUE, connection will be established only when required
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 */
class DibiOracleDriver extends DibiObject implements IDibiDriver
{

	/** @var resource  Connection resource */
	private $connection;


	/** @var resource  Resultset resource */
	private $resultSet;


	/** @var bool */
	private $autocommit = TRUE;



	/**
	 * @throws DibiException
	 */
	public function __construct()
	{
		if (!extension_loaded('oci8')) {
			throw new DibiDriverException("PHP extension 'oci8' is not loaded.");
		}
	}



	/**
	 * Connects to a database.
	 * @return void
	 * @throws DibiException
	 */
	public function connect(array &$config)
	{
		DibiConnection::alias($config, 'username', 'user');
		DibiConnection::alias($config, 'password', 'pass');
		DibiConnection::alias($config, 'database', 'db');
		DibiConnection::alias($config, 'charset');

		$this->connection = @oci_new_connect($config['username'], $config['password'], $config['database'], $config['charset']); // intentionally @

		if (!$this->connection) {
			$err = oci_error();
			throw new DibiDriverException($err['message'], $err['code']);
		}
	}



	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		oci_close($this->connection);
	}



	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return IDibiDriver|NULL
	 * @throws DibiDriverException
	 */
	public function query($sql)
	{

		$this->resultSet = oci_parse($this->connection, $sql);
		if ($this->resultSet) {
			oci_execute($this->resultSet, $this->autocommit ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT);
			$err = oci_error($this->resultSet);
			if ($err) {
				throw new DibiDriverException($err['message'], $err['code'], $sql);
			}
		} else {
			$err = oci_error($this->connection);
			throw new DibiDriverException($err['message'], $err['code'], $sql);
		}

		return is_resource($this->resultSet) ? clone $this : NULL;
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function affectedRows()
	{
		throw new NotImplementedException;
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function insertId($sequence)
	{
		throw new NotSupportedException('Oracle does not support autoincrementing.');
	}



	/**
	 * Begins a transaction (if supported).
	 * @return void
	 * @throws DibiDriverException
	 */
	public function begin()
	{
		$this->autocommit = FALSE;
	}



	/**
	 * Commits statements in a transaction.
	 * @return void
	 * @throws DibiDriverException
	 */
	public function commit()
	{
		if (!oci_commit($this->connection)) {
			$err = oci_error($this->connection);
			throw new DibiDriverException($err['message'], $err['code']);
		}
		$this->autocommit = TRUE;
	}



	/**
	 * Rollback changes in a transaction.
	 * @return void
	 * @throws DibiDriverException
	 */
	public function rollback()
	{
		if (!oci_rollback($this->connection)) {
			$err = oci_error($this->connection);
			throw new DibiDriverException($err['message'], $err['code']);
		}
		$this->autocommit = TRUE;
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
	 * Encodes data for use in an SQL statement.
	 * @param  string    value
	 * @param  string    type (dibi::FIELD_TEXT, dibi::FIELD_BOOL, ...)
	 * @return string    encoded value
	 * @throws InvalidArgumentException
	 */
	public function escape($value, $type)
	{
		switch ($type) {
		case dibi::FIELD_TEXT:
		case dibi::FIELD_BINARY:
			return "'" . str_replace("'", "''", $value) . "'"; // TODO: not tested

		case dibi::IDENTIFIER:
			// @see http://download.oracle.com/docs/cd/B10500_01/server.920/a96540/sql_elements9a.htm
			$value = str_replace('"', '""', $value);
			return '"' . str_replace('.', '"."', $value) . '"';

		case dibi::FIELD_BOOL:
			return $value ? 1 : 0;

		case dibi::FIELD_DATE:
			return date("U", $value);

		case dibi::FIELD_DATETIME:
			return date("U", $value);

		default:
			throw new InvalidArgumentException('Unsupported type.');
		}
	}



	/**
	 * Decodes data from result set.
	 * @param  string    value
	 * @param  string    type (dibi::FIELD_BINARY)
	 * @return string    decoded value
	 * @throws InvalidArgumentException
	 */
	public function unescape($value, $type)
	{
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
		$sql .= ' LIMIT ' . $limit . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
	}



	/********************* result set ****************d*g**/



	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	public function rowCount()
	{
		return oci_num_rows($this->resultSet);
	}



	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 * @internal
	 */
	public function fetch($assoc)
	{
		return oci_fetch_array($this->resultSet, ($assoc ? OCI_ASSOC : OCI_NUM) | OCI_RETURN_NULLS);
	}



	/**
	 * Moves cursor position without fetching row.
	 * @param  int      the 0-based cursor pos to seek to
	 * @return boolean  TRUE on success, FALSE if unable to seek to specified record
	 * @throws DibiException
	 */
	public function seek($row)
	{
		throw new NotImplementedException;
	}



	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	public function free()
	{
		oci_free_statement($this->resultSet);
		$this->resultSet = NULL;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getColumnsMeta()
	{
		$count = oci_num_fields($this->resultSet);
		$res = array();
		for ($i = 1; $i <= $count; $i++) {
			$res[] = array(
				'name'      => oci_field_name($this->resultSet, $i),
				'table'     => NULL,
				'fullname'  => oci_field_name($this->resultSet, $i),
				'nativetype'=> oci_field_type($this->resultSet, $i),
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
