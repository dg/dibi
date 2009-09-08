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
 *   - 'resource' - connection resource (optional)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiMySqliDriver extends DibiObject implements IDibiDriver
{
	const ERROR_ACCESS_DENIED = 1045;
	const ERROR_DUPLICATE_ENTRY = 1062;
	const ERROR_DATA_TRUNCATED = 1265;

	/** @var mysqli  Connection resource */
	private $connection;

	/** @var mysqli_result  Resultset resource */
	private $resultSet;

	/** @var bool  Is buffered (seekable and countable)? */
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
	 * @return void
	 * @throws DibiException
	 */
	public function connect(array &$config)
	{
		DibiConnection::alias($config, 'options');
		DibiConnection::alias($config, 'database');

		if (isset($config['resource'])) {
			$this->connection = $config['resource'];

		} else {
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
	 * @return void
	 */
	public function disconnect()
	{
		mysqli_close($this->connection);
	}



	/**
	 * Executes the SQL query.
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
	 * Retrieves information about the most recently executed query.
	 * @return array
	 */
	public function getInfo()
	{
		$res = array();
		preg_match_all('#(.+?): +(\d+) *#', mysqli_info($this->connection), $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$res[$m[1]] = (int) $m[2];
		}
		return $res;
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function getAffectedRows()
	{
		return mysqli_affected_rows($this->connection);
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function getInsertId($sequence)
	{
		return mysqli_insert_id($this->connection);
	}



	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function begin($savepoint = NULL)
	{
		$this->query($savepoint ? "SAVEPOINT $savepoint" : 'START TRANSACTION');
	}



	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function commit($savepoint = NULL)
	{
		$this->query($savepoint ? "RELEASE SAVEPOINT $savepoint" : 'COMMIT');
	}



	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function rollback($savepoint = NULL)
	{
		$this->query($savepoint ? "ROLLBACK TO SAVEPOINT $savepoint" : 'ROLLBACK');
	}



	/**
	 * Returns the connection resource.
	 * @return mysqli
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
			return "'" . mysqli_real_escape_string($this->connection, $value) . "'";

		case dibi::BINARY:
			return "_binary'" . mysqli_real_escape_string($this->connection, $value) . "'";

		case dibi::IDENTIFIER:
			$value = str_replace('`', '``', $value);
			return '`' . str_replace('.', '`.`', $value) . '`';

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

		// see http://dev.mysql.com/doc/refman/5.0/en/select.html
		$sql .= ' LIMIT ' . ($limit < 0 ? '18446744073709551615' : (int) $limit)
			. ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
	}



	/********************* result set ****************d*g**/



	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	public function getRowCount()
	{
		if (!$this->buffered) {
			throw new DibiDriverException('Row count is not available for unbuffered queries.');
		}
		return mysqli_num_rows($this->resultSet);
	}



	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 * @internal
	 */
	public function fetch($assoc)
	{
		return mysqli_fetch_array($this->resultSet, $assoc ? MYSQLI_ASSOC : MYSQLI_NUM);
	}



	/**
	 * Moves cursor position without fetching row.
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
	 * @return void
	 */
	public function free()
	{
		mysqli_free_result($this->resultSet);
		$this->resultSet = NULL;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getColumnsMeta()
	{
		static $types;
		if (empty($types)) {
			$consts = get_defined_constants(TRUE);
			foreach ($consts['mysqli'] as $key => $value) {
				if (strncmp($key, 'MYSQLI_TYPE_', 12) === 0) {
					$types[$value] = substr($key, 12);
				}
			}
		}

		$count = mysqli_num_fields($this->resultSet);
		$res = array();
		for ($i = 0; $i < $count; $i++) {
			$row = (array) mysqli_fetch_field_direct($this->resultSet, $i);
			$res[] = array(
				'name' => $row['name'],
				'table' => $row['orgtable'],
				'fullname' => $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'],
				'nativetype' => $types[$row['type']],
				'vendor' => $row,
			);
		}
		return $res;
	}



	/**
	 * Returns the result set resource.
	 * @return mysqli_result
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
		/*$this->query("
			SELECT TABLE_NAME as name, TABLE_TYPE = 'VIEW' as view
			FROM INFORMATION_SCHEMA.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
		");*/
		$this->query("SHOW FULL TABLES");
		$res = array();
		while ($row = $this->fetch(FALSE)) {
			$res[] = array(
				'name' => $row[0],
				'view' => isset($row[1]) && $row[1] === 'VIEW',
			);
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
		/*$table = $this->escape($table, dibi::TEXT);
		$this->query("
			SELECT *
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_NAME = $table AND TABLE_SCHEMA = DATABASE()
		");*/
		$this->query("SHOW FULL COLUMNS FROM `$table`");
		$res = array();
		while ($row = $this->fetch(TRUE)) {
			$type = explode('(', $row['Type']);
			$res[] = array(
				'name' => $row['Field'],
				'table' => $table,
				'nativetype' => strtoupper($type[0]),
				'size' => isset($type[1]) ? (int) $type[1] : NULL,
				'nullable' => $row['Null'] === 'YES',
				'default' => $row['Default'],
				'autoincrement' => $row['Extra'] === 'auto_increment',
				'vendor' => $row,
			);
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns metadata for all indexes in a table.
	 * @param  string
	 * @return array
	 */
	public function getIndexes($table)
	{
		/*$table = $this->escape($table, dibi::TEXT);
		$this->query("
			SELECT *
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE TABLE_NAME = $table AND TABLE_SCHEMA = DATABASE()
			AND REFERENCED_COLUMN_NAME IS NULL
		");*/
		$this->query("SHOW INDEX FROM `$table`");
		$res = array();
		while ($row = $this->fetch(TRUE)) {
			$res[$row['Key_name']]['name'] = $row['Key_name'];
			$res[$row['Key_name']]['unique'] = !$row['Non_unique'];
			$res[$row['Key_name']]['primary'] = $row['Key_name'] === 'PRIMARY';
			$res[$row['Key_name']]['columns'][$row['Seq_in_index'] - 1] = $row['Column_name'];
		}
		$this->free();
		return array_values($res);
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
