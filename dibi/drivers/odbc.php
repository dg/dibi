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
 * For more information please see http://dibiphp.com/
 *
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com/
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
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiOdbcDriver extends /*Nette::*/Object implements IDibiDriver
{

	/**
	 * Connection resource.
	 * @var resource
	 */
	private $connection;


	/**
	 * Resultset resource.
	 * @var resource
	 */
	private $resultset;


	/**
	 * Cursor.
	 * @var int
	 */
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
	 *
	 * @return void
	 * @throws DibiException
	 */
	public function connect(array &$config)
	{
		DibiConnection::alias($config, 'username', 'user');
		DibiConnection::alias($config, 'password', 'pass');

		// default values
		if (!isset($config['username'])) $config['username'] = ini_get('odbc.default_user');
		if (!isset($config['password'])) $config['password'] = ini_get('odbc.default_pw');
		if (!isset($config['dsn'])) $config['dsn'] = ini_get('odbc.default_db');

		if (empty($config['persistent'])) {
			$this->connection = @odbc_connect($config['dsn'], $config['username'], $config['password']); // intentionally @
		} else {
			$this->connection = @odbc_pconnect($config['dsn'], $config['username'], $config['password']); // intentionally @
		}

		if (!is_resource($this->connection)) {
			throw new DibiDriverException(odbc_errormsg() . ' ' . odbc_error());
		}
	}



	/**
	 * Disconnects from a database.
	 *
	 * @return void
	 */
	public function disconnect()
	{
		odbc_close($this->connection);
	}



	/**
	 * Executes the SQL query.
	 *
	 * @param  string      SQL statement.
	 * @return IDibiDriver|NULL
	 * @throws DibiDriverException
	 */
	public function query($sql)
	{
		$this->resultset = @odbc_exec($this->connection, $sql); // intentionally @

		if ($this->resultset === FALSE) {
			$this->throwException($sql);
		}

		return is_resource($this->resultset) ? clone $this : NULL;
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 *
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function affectedRows()
	{
		return odbc_num_rows($this->resultset);
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 *
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function insertId($sequence)
	{
		throw new NotSupportedException('ODBC does not support autoincrementing.');
	}



	/**
	 * Begins a transaction (if supported).
	 * @return void
	 * @throws DibiDriverException
	 */
	public function begin()
	{
		if (!odbc_autocommit($this->connection, FALSE)) {
			$this->throwException();
		}
	}



	/**
	 * Commits statements in a transaction.
	 * @return void
	 * @throws DibiDriverException
	 */
	public function commit()
	{
		if (!odbc_commit($this->connection)) {
			$this->throwException();
		}
		odbc_autocommit($this->connection, TRUE);
	}



	/**
	 * Rollback changes in a transaction.
	 * @return void
	 * @throws DibiDriverException
	 */
	public function rollback()
	{
		if (!odbc_rollback($this->connection)) {
			$this->throwException();
		}
		odbc_autocommit($this->connection, TRUE);
	}



	/**
	 * Encodes data for use in an SQL statement.
	 *
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
			return "'" . str_replace("'", "''", $value) . "'";

		case dibi::IDENTIFIER:
			return '[' . str_replace('.', '].[', $value) . ']';

		case dibi::FIELD_BOOL:
			return $value ? -1 : 0;

		case dibi::FIELD_DATE:
			return date("#m/d/Y#", $value);

		case dibi::FIELD_DATETIME:
			return date("#m/d/Y H:i:s#", $value);

		default:
			throw new InvalidArgumentException('Unsupported type.');
		}
	}



	/**
	 * Decodes data from resultset.
	 *
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
	 *
	 * @param  string &$sql  The SQL query that will be modified.
	 * @param  int $limit
	 * @param  int $offset
	 * @return void
	 */
	public function applyLimit(&$sql, $limit, $offset)
	{
		// offset suppot is missing...
		if ($limit >= 0) {
			$sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ')';
		}

		if ($offset) throw new InvalidArgumentException('Offset is not implemented in driver odbc.');
	}



	/**
	 * Returns the number of rows in a result set.
	 *
	 * @return int
	 */
	public function rowCount()
	{
		// will return -1 with many drivers :-(
		return odbc_num_rows($this->resultset);
	}



	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * internal usage only
	 *
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 */
	public function fetch($type)
	{
		if ($type) {
			return odbc_fetch_array($this->resultset, ++$this->row);
		} else {
			$set = $this->resultset;
			if (!odbc_fetch_row($set, ++$this->row)) return FALSE;
			$count = odbc_num_fields($set);
			$cols = array();
			for ($i = 1; $i <= $count; $i++) $cols[] = odbc_result($set, $i);
			return $cols;
		}
	}



	/**
	 * Moves cursor position without fetching row.
	 *
	 * @param  int      the 0-based cursor pos to seek to
	 * @return boolean  TRUE on success, FALSE if unable to seek to specified record
	 * @throws DibiException
	 */
	public function seek($row)
	{
		$this->row = $row;
		return TRUE;
	}



	/**
	 * Frees the resources allocated for this result set.
	 *
	 * @return void
	 */
	public function free()
	{
		odbc_free_result($this->resultset);
		$this->resultset = NULL;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 *
	 * @return array
	 */
	public function getColumnsMeta()
	{
		$count = odbc_num_fields($this->resultset);
		$meta = array();
		for ($i = 1; $i <= $count; $i++) {
			// items 'name' and 'table' are required
			$meta[] = array(
				'name'      => odbc_field_name($this->resultset, $i),
				'table'     => NULL,
				'type'      => odbc_field_type($this->resultset, $i),
				'length'    => odbc_field_len($this->resultset, $i),
				'scale'     => odbc_field_scale($this->resultset, $i),
				'precision' => odbc_field_precision($this->resultset, $i),
			);
		}
		return $meta;
	}



	/**
	 * Converts database error to DibiDriverException.
	 *
	 * @throws DibiDriverException
	 */
	protected function throwException($sql = NULL)
	{
		throw new DibiDriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection), 0, $sql);
	}



	/**
	 * Returns the connection resource.
	 *
	 * @return mixed
	 */
	public function getResource()
	{
		return $this->connection;
	}



	/**
	 * Returns the resultset resource.
	 *
	 * @return mixed
	 */
	public function getResultResource()
	{
		return $this->resultset;
	}



	/**
	 * Gets a information of the current database.
	 *
	 * @return DibiReflection
	 */
	function getDibiReflection()
	{}

}
