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
 * @version    $Revision$ $Date$
 */
class DibiMySqlDriver extends /*Nette::*/Object implements IDibiDriver
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
			$this->connection = @mysql_connect($host, $config['username'], $config['password'], TRUE, $config['options']);
		} else {
			$this->connection = @mysql_pconnect($host, $config['username'], $config['password'], $config['options']);
		}

		if (!is_resource($this->connection)) {
			throw new DibiDriverException(mysql_error(), mysql_errno());
		}

		if (isset($config['charset'])) {
			$ok = FALSE;
			if (function_exists('mysql_set_charset')) {
				// affects the character set used by mysql_real_escape_string() (was added in MySQL 5.0.7 and PHP 5.2.3)
				$ok = @mysql_set_charset($config['charset'], $this->connection);
			}
			if (!$ok) $ok = @mysql_query("SET NAMES '$config[charset]'", $this->connection);
			if (!$ok) $this->throwException();
		}

		if (isset($config['database'])) {
			@mysql_select_db($config['database'], $this->connection) or $this->throwException();
		}

		if (isset($config['sqlmode'])) {
			if (!@mysql_query("SET sql_mode='$config[sqlmode]'", $this->connection)) $this->throwException();
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
	 * @return bool        have resultset?
	 * @throws DibiDriverException
	 */
	public function query($sql)
	{
		if ($this->buffered) {
			$this->resultset = @mysql_query($sql, $this->connection);
		} else {
			$this->resultset = @mysql_unbuffered_query($sql, $this->connection);
		}

		if (mysql_errno($this->connection)) {
			$this->throwException($sql);
		}

		return is_resource($this->resultset);
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
	 * Format to SQL command.
	 *
	 * @param  string    value
	 * @param  string    type (dibi::FIELD_TEXT, dibi::FIELD_BOOL, dibi::FIELD_DATE, dibi::FIELD_DATETIME, dibi::IDENTIFIER)
	 * @return string    formatted value
	 * @throws InvalidArgumentException
	 */
	public function format($value, $type)
	{
		if ($type === dibi::FIELD_TEXT) return "'" . mysql_real_escape_string($value, $this->connection) . "'";
		if ($type === dibi::IDENTIFIER) return '`' . str_replace('.', '`.`', $value) . '`';
		if ($type === dibi::FIELD_BOOL) return $value ? 1 : 0;
		if ($type === dibi::FIELD_DATE) return date("'Y-m-d'", $value);
		if ($type === dibi::FIELD_DATETIME) return date("'Y-m-d H:i:s'", $value);
		throw new InvalidArgumentException('Unsupported formatting type.');
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
		return mysql_num_rows($this->resultset);
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
		return mysql_fetch_array($this->resultset, $type ? MYSQL_ASSOC : MYSQL_NUM);
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

		return mysql_data_seek($this->resultset, $row);
	}



	/**
	 * Frees the resources allocated for this result set.
	 *
	 * @return void
	 */
	public function free()
	{
		mysql_free_result($this->resultset);
		$this->resultset = NULL;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 *
	 * @return array
	 */
	public function getColumnsMeta()
	{
		$count = mysql_num_fields($this->resultset);
		$meta = array();
		for ($i = 0; $i < $count; $i++) {
			// items 'name' and 'table' are required
			$meta[] = (array) mysql_fetch_field($this->resultset, $i);
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
		throw new DibiDriverException(mysql_error($this->connection), mysql_errno($this->connection), $sql);
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
