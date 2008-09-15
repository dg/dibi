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
 * The dibi driver for MySQL database via improved extension.
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
 *   - 'options' - driver specific constants (MYSQLI_*)
 *   - 'sqlmode' - see http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html
 *   - 'lazy' - if TRUE, connection will be established only when required
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 */
class DibiMySqliDriver extends DibiObject implements IDibiDriver
{

	/**
	 * Connection resource.
	 * @var mysqli
	 */
	private $connection;


	/**
	 * Resultset resource.
	 * @var mysqli_result
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
		if (!extension_loaded('mysqli')) {
			throw new DibiDriverException("PHP extension 'mysqli' is not loaded.");
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
		DibiConnection::alias($config, 'database');

		// default values
		if (!isset($config['username'])) $config['username'] = ini_get('mysqli.default_user');
		if (!isset($config['password'])) $config['password'] = ini_get('mysqli.default_pw');
		if (!isset($config['socket'])) $config['socket'] = ini_get('mysqli.default_socket');
		if (!isset($config['port'])) $config['port'] = NULL;
		if (!isset($config['host'])) {
			$host = ini_get('mysqli.default_host');
			if ($host) {
				$config['host'] = $host;
				$config['port'] = ini_get('mysqli.default_port');
			} else {
				$config['host'] = NULL;
				$config['port'] = NULL;
			}
		}

		$this->connection = mysqli_init();
		@mysqli_real_connect($this->connection, $config['host'], $config['username'], $config['password'], $config['database'], $config['port'], $config['socket'], $config['options']); // intentionally @

		if ($errno = mysqli_connect_errno()) {
			throw new DibiDriverException(mysqli_connect_error(), $errno);
		}

		if (isset($config['charset'])) {
			$ok = FALSE;
			if (version_compare(PHP_VERSION , '5.1.5', '>=')) {
				// affects the character set used by mysql_real_escape_string() (was added in MySQL 5.0.7 and PHP 5.0.5, fixed in PHP 5.1.5)
				$ok = @mysqli_set_charset($this->connection, $config['charset']); // intentionally @
			}
			if (!$ok) {
				$ok = @mysqli_query($this->connection, "SET NAMES '$config[charset]'"); // intentionally @
				if (!$ok) {
					throw new DibiDriverException(mysqli_error($this->connection), mysqli_errno($this->connection));
				}
			}
		}

		if (isset($config['sqlmode'])) {
			if (!@mysqli_query($this->connection, "SET sql_mode='$config[sqlmode]'")) { // intentionally @
				throw new DibiDriverException(mysqli_error($this->connection), mysqli_errno($this->connection));
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
		mysqli_close($this->connection);
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
		$this->resultSet = @mysqli_query($this->connection, $sql, $this->buffered ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT); // intentionally @

		if (mysqli_errno($this->connection)) {
			throw new DibiDriverException(mysqli_error($this->connection), mysqli_errno($this->connection), $sql);
		}

		return is_object($this->resultSet) ? clone $this : NULL;
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 *
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function affectedRows()
	{
		return mysqli_affected_rows($this->connection);
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 *
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function insertId($sequence)
	{
		return mysqli_insert_id($this->connection);
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
			return "'" . mysqli_real_escape_string($this->connection, $value) . "'";

		case dibi::IDENTIFIER:
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
		return mysqli_num_rows($this->resultSet);
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
		return mysqli_fetch_array($this->resultSet, $type ? MYSQLI_ASSOC : MYSQLI_NUM);
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
		return mysqli_data_seek($this->resultSet, $row);
	}



	/**
	 * Frees the resources allocated for this result set.
	 *
	 * @return void
	 */
	public function free()
	{
		mysqli_free_result($this->resultSet);
		$this->resultSet = NULL;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 *
	 * @return array
	 */
	public function getColumnsMeta()
	{
		$count = mysqli_num_fields($this->resultSet);
		$meta = array();
		for ($i = 0; $i < $count; $i++) {
			// items 'name' and 'table' are required
			$meta[] = (array) mysqli_fetch_field_direct($this->resultSet, $i);
		}
		return $meta;
	}



	/**
	 * Returns the connection resource.
	 *
	 * @return mysqli
	 */
	public function getResource()
	{
		return $this->connection;
	}



	/**
	 * Returns the result set resource.
	 *
	 * @return mysqli_result
	 */
	public function getResultResource()
	{
		return $this->resultSet;
	}



	/**
	 * Gets a information of the current database.
	 *
	 * @return DibiReflection
	 */
	function getDibiReflection()
	{}

}
