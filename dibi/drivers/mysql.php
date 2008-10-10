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
 * The dibi driver for MySQL database.
 *
 * Connection options:
 *   - 'host' - the MySQL server host name
 *   - 'port' - the port number to attempt to connect to the MySQL server
 *   - 'socket' - the socket or named pipe
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'persistent' - try to find a persistent link?
 *   - 'database' - the database name to select
 *   - 'charset' - character encoding to set
 *   - 'unbuffered' - sends query without fetching and buffering the result rows automatically?
 *   - 'options' - driver specific constants (MYSQL_*)
 *   - 'sqlmode' - see http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html
 *   - 'lazy' - if TRUE, connection will be established only when required
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 */
class DibiMySqlDriver extends DibiObject implements IDibiDriver
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
	private $resultSet;


	/**
	 * Is buffered (seekable and countable)?
	 * @var bool
	 */
	private $buffered;



	/**
	 * @throws DibiException
	 */
	public function __construct()
	{
		if (!extension_loaded('mysql')) {
			throw new DibiDriverException("PHP extension 'mysql' is not loaded.");
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
		DibiConnection::alias($config, 'host', 'hostname');
		DibiConnection::alias($config, 'options');

		// default values
		if (!isset($config['username'])) $config['username'] = ini_get('mysql.default_user');
		if (!isset($config['password'])) $config['password'] = ini_get('mysql.default_password');
		if (!isset($config['host'])) {
			$host = ini_get('mysql.default_host');
			if ($host) {
				$config['host'] = $host;
				$config['port'] = ini_get('mysql.default_port');
			} else {
				if (!isset($config['socket'])) $config['socket'] = ini_get('mysql.default_socket');
				$config['host'] = NULL;
			}
		}

		if (empty($config['socket'])) {
			$host = $config['host'] . (empty($config['port']) ? '' : ':' . $config['port']);
		} else {
			$host = ':' . $config['socket'];
		}

		if (empty($config['persistent'])) {
			$this->connection = @mysql_connect($host, $config['username'], $config['password'], TRUE, $config['options']); // intentionally @
		} else {
			$this->connection = @mysql_pconnect($host, $config['username'], $config['password'], $config['options']); // intentionally @
		}

		if (!is_resource($this->connection)) {
			throw new DibiDriverException(mysql_error(), mysql_errno());
		}

		if (isset($config['charset'])) {
			$ok = FALSE;
			if (function_exists('mysql_set_charset')) {
				// affects the character set used by mysql_real_escape_string() (was added in MySQL 5.0.7 and PHP 5.2.3)
				$ok = @mysql_set_charset($config['charset'], $this->connection); // intentionally @
			}
			if (!$ok) {
				$ok = @mysql_query("SET NAMES '$config[charset]'", $this->connection); // intentionally @
				if (!$ok) {
					throw new DibiDriverException(mysql_error($this->connection), mysql_errno($this->connection));
				}
			}
		}

		if (isset($config['database'])) {
			if (!@mysql_select_db($config['database'], $this->connection)) { // intentionally @
				throw new DibiDriverException(mysql_error($this->connection), mysql_errno($this->connection));
			}
		}

		if (isset($config['sqlmode'])) {
			if (!@mysql_query("SET sql_mode='$config[sqlmode]'", $this->connection)) { // intentionally @
				throw new DibiDriverException(mysql_error($this->connection), mysql_errno($this->connection));
			}
		}

		$this->buffered = empty($config['unbuffered']);
	}



	/**
	 * Disconnects from a database.
	 *
	 * @return void
	 */
	public function disconnect()
	{
		mysql_close($this->connection);
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
		if ($this->buffered) {
			$this->resultSet = @mysql_query($sql, $this->connection); // intentionally @
		} else {
			$this->resultSet = @mysql_unbuffered_query($sql, $this->connection); // intentionally @
		}

		if (mysql_errno($this->connection)) {
			throw new DibiDriverException(mysql_error($this->connection), mysql_errno($this->connection), $sql);
		}

		return is_resource($this->resultSet) ? clone $this : NULL;
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 *
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function affectedRows()
	{
		return mysql_affected_rows($this->connection);
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 *
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function insertId($sequence)
	{
		return mysql_insert_id($this->connection);
	}



	/**
	 * Begins a transaction (if supported).
	 * @return void
	 * @throws DibiDriverException
	 */
	public function begin()
	{
		$this->query('START TRANSACTION');
	}



	/**
	 * Commits statements in a transaction.
	 * @return void
	 * @throws DibiDriverException
	 */
	public function commit()
	{
		$this->query('COMMIT');
	}



	/**
	 * Rollback changes in a transaction.
	 * @return void
	 * @throws DibiDriverException
	 */
	public function rollback()
	{
		$this->query('ROLLBACK');
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



	/********************* SQL ****************d*g**/



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
			return "'" . mysql_real_escape_string($value, $this->connection) . "'";

		case dibi::IDENTIFIER:
			// @see http://dev.mysql.com/doc/refman/5.0/en/identifiers.html
			$value = str_replace('`', '``', $value);
			return '`' . str_replace('.', '`.`', $value) . '`';

		case dibi::FIELD_BOOL:
			return $value ? 1 : 0;

		case dibi::FIELD_DATE:
			return date("'Y-m-d'", $value);

		case dibi::FIELD_DATETIME:
			return date("'Y-m-d H:i:s'", $value);

		default:
			throw new InvalidArgumentException('Unsupported type.');
		}
	}



	/**
	 * Decodes data from result set.
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
		if ($limit < 0 && $offset < 1) return;

		// see http://dev.mysql.com/doc/refman/5.0/en/select.html
		$sql .= ' LIMIT ' . ($limit < 0 ? '18446744073709551615' : (int) $limit)
			. ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
	}



	/********************* result set ****************d*g**/



	/**
	 * Returns the number of rows in a result set.
	 *
	 * @return int
	 */
	public function rowCount()
	{
		if (!$this->buffered) {
			throw new DibiDriverException('Row count is not available for unbuffered queries.');
		}
		return mysql_num_rows($this->resultSet);
	}



	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * internal usage only
	 *
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 */
	public function fetch($assoc)
	{
		return mysql_fetch_array($this->resultSet, $assoc ? MYSQL_ASSOC : MYSQL_NUM);
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
		if (!$this->buffered) {
			throw new DibiDriverException('Cannot seek an unbuffered result set.');
		}

		return mysql_data_seek($this->resultSet, $row);
	}



	/**
	 * Frees the resources allocated for this result set.
	 *
	 * @return void
	 */
	public function free()
	{
		mysql_free_result($this->resultSet);
		$this->resultSet = NULL;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 *
	 * @return array
	 */
	public function getColumnsMeta()
	{
		$count = mysql_num_fields($this->resultSet);
		$meta = array();
		for ($i = 0; $i < $count; $i++) {
			$info = (array) mysql_fetch_field($this->resultSet, $i);
			$info['nativetype'] = $info['type'];
			$meta[] = $info;
		}
		return $meta;
	}



	/**
	 * Returns the result set resource.
	 *
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
		$this->query("SHOW TABLES");
		$res = array();
		while ($row = mysql_fetch_array($this->resultSet, MYSQL_NUM)) {
			$res[] = array('name' => $row[0]);
		}
		$this->free();
		return $res;
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
