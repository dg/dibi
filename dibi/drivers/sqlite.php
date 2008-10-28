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
 * The dibi driver for SQLite database.
 *
 * Connection options:
 *   - 'database' (or 'file') - the filename of the SQLite database
 *   - 'persistent' - try to find a persistent link?
 *   - 'unbuffered' - sends query without fetching and buffering the result rows automatically?
 *   - 'lazy' - if TRUE, connection will be established only when required
 *   - 'formatDate' - how to format date in SQL (@see date)
 *   - 'formatDateTime' - how to format datetime in SQL (@see date)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 */
class DibiSqliteDriver extends DibiObject implements IDibiDriver
{

	/** @var resource  Connection resource */
	private $connection;


	/** @var resource  Resultset resource */
	private $resultSet;


	/** @var bool  Is buffered (seekable and countable)? */
	private $buffered;


	/** @var string  Date and datetime format */
	private $fmtDate, $fmtDateTime;



	/**
	 * @throws DibiException
	 */
	public function __construct()
	{
		if (!extension_loaded('sqlite')) {
			throw new DibiDriverException("PHP extension 'sqlite' is not loaded.");
		}
	}



	/**
	 * Connects to a database.
	 * @return void
	 * @throws DibiException
	 */
	public function connect(array &$config)
	{
		DibiConnection::alias($config, 'database', 'file');
		$this->fmtDate = isset($config['formatDate']) ? $config['formatDate'] : 'U';
		$this->fmtDateTime = isset($config['formatDateTime']) ? $config['formatDateTime'] : 'U';

		$errorMsg = '';
		if (empty($config['persistent'])) {
			$this->connection = @sqlite_open($config['database'], 0666, $errorMsg); // intentionally @
		} else {
			$this->connection = @sqlite_popen($config['database'], 0666, $errorMsg); // intentionally @
		}

		if (!$this->connection) {
			throw new DibiDriverException($errorMsg);
		}

		$this->buffered = empty($config['unbuffered']);
	}



	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		sqlite_close($this->connection);
	}



	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return IDibiDriver|NULL
	 * @throws DibiDriverException
	 */
	public function query($sql)
	{
		DibiDriverException::tryError();
		if ($this->buffered) {
			$this->resultSet = sqlite_query($this->connection, $sql);
		} else {
			$this->resultSet = sqlite_unbuffered_query($this->connection, $sql);
		}
		if (DibiDriverException::catchError($msg)) {
			throw new DibiDriverException($msg, sqlite_last_error($this->connection), $sql);
		}

		return is_resource($this->resultSet) ? clone $this : NULL;
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function affectedRows()
	{
		return sqlite_changes($this->connection);
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function insertId($sequence)
	{
		return sqlite_last_insert_rowid($this->connection);
	}



	/**
	 * Begins a transaction (if supported).
	 * @return void
	 * @throws DibiDriverException
	 */
	public function begin()
	{
		$this->query('BEGIN');
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
			return "'" . sqlite_escape_string($value) . "'";

		case dibi::IDENTIFIER:
			return '[' . str_replace('.', '].[', strtr($value, '[]', '  ')) . ']';

		case dibi::FIELD_BOOL:
			return $value ? 1 : 0;

		case dibi::FIELD_DATE:
			return date($this->fmtDate, $value);

		case dibi::FIELD_DATETIME:
			return date($this->fmtDateTime, $value);

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
		if (!$this->buffered) {
			throw new DibiDriverException('Row count is not available for unbuffered queries.');
		}
		return sqlite_num_rows($this->resultSet);
	}



	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 * @internal
	 */
	public function fetch($assoc)
	{
		$row = sqlite_fetch_array($this->resultSet, $assoc ? SQLITE_ASSOC : SQLITE_NUM);
		if ($assoc && $row) {
			$tmp = array();
			foreach ($row as $k => $v) {
				$tmp[str_replace(array('[', ']'), '', $k)] = $v;
			}
			return $tmp;
		}
		return $row;
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
		return sqlite_seek($this->resultSet, $row);
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
	 */
	public function getColumnsMeta()
	{
		$count = sqlite_num_fields($this->resultSet);
		$res = array();
		for ($i = 0; $i < $count; $i++) {
			$name = str_replace(array('[', ']'), '', sqlite_field_name($this->resultSet, $i));
			$pair = explode('.', $name);
			$res[] = array(
				'name'  => isset($pair[1]) ? $pair[1] : $pair[0],
				'table' => isset($pair[1]) ? $pair[0] : NULL,
				'fullname' => $name,
				'nativetype' => NULL,
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
		$this->query("
			SELECT name, type = 'view' as view FROM sqlite_master WHERE type IN ('table', 'view')
			UNION ALL
			SELECT name, type = 'view' as view FROM sqlite_temp_master WHERE type IN ('table', 'view')
			ORDER BY name
		");
		$res = sqlite_fetch_all($this->resultSet, SQLITE_ASSOC);
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
