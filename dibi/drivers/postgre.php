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
 * The dibi driver for PostgreSQL database.
 *
 * Connection options:
 *   - 'host','hostaddr','port','dbname','user','password','connect_timeout','options','sslmode','service' - see PostgreSQL API
 *   - 'string' - or use connection string
 *   - 'persistent' - try to find a persistent link?
 *   - 'charset' - character encoding to set
 *   - 'schema' - the schema search path
 *   - 'lazy' - if TRUE, connection will be established only when required
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 */
class DibiPostgreDriver extends DibiObject implements IDibiDriver
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
	 * Escape method.
	 * @var bool
	 */
	private $escMethod = FALSE;



	/**
	 * @throws DibiException
	 */
	public function __construct()
	{
		if (!extension_loaded('pgsql')) {
			throw new DibiDriverException("PHP extension 'pgsql' is not loaded.");
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
		if (isset($config['string'])) {
			$string = $config['string'];
		} else {
			$string = '';
			foreach (array('host','hostaddr','port','dbname','user','password','connect_timeout','options','sslmode','service') as $key) {
				if (isset($config[$key])) $string .= $key . '=' . $config[$key] . ' ';
			}
		}

		DibiDriverException::tryError();
		if (empty($config['persistent'])) {
			$this->connection = pg_connect($string, PGSQL_CONNECT_FORCE_NEW);
		} else {
			$this->connection = pg_pconnect($string, PGSQL_CONNECT_FORCE_NEW);
		}
		if (DibiDriverException::catchError($msg)) {
			throw new DibiDriverException($msg, 0);
		}

		if (!is_resource($this->connection)) {
			throw new DibiDriverException('Connecting error.');
		}

		if (isset($config['charset'])) {
			DibiDriverException::tryError();
			pg_set_client_encoding($this->connection, $config['charset']);
			if (DibiDriverException::catchError($msg)) {
				throw new DibiDriverException($msg, 0);
			}
		}

		if (isset($config['schema'])) {
			$this->query('SET search_path TO ' . $config['schema']);
		}

		$this->escMethod = version_compare(PHP_VERSION , '5.2.0', '>=');
	}



	/**
	 * Disconnects from a database.
	 *
	 * @return void
	 */
	public function disconnect()
	{
		pg_close($this->connection);
	}



	/**
	 * Executes the SQL query.
	 *
	 * @param  string      SQL statement.
	 * @param  bool        update affected rows?
	 * @return IDibiDriver|NULL
	 * @throws DibiDriverException
	 */
	public function query($sql)
	{
		$this->resultSet = @pg_query($this->connection, $sql); // intentionally @

		if ($this->resultSet === FALSE) {
			throw new DibiDriverException(pg_last_error($this->connection), 0, $sql);
		}

		return is_resource($this->resultSet) && pg_num_fields($this->resultSet) ? clone $this : NULL;
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 *
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function affectedRows()
	{
		return pg_affected_rows($this->resultSet);
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 *
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function insertId($sequence)
	{
		if ($sequence === NULL) {
			// PostgreSQL 8.1 is needed
			$has = $this->query("SELECT LASTVAL()");
		} else {
			$has = $this->query("SELECT CURRVAL('$sequence')");
		}

		if (!$has) return FALSE;

		$row = $this->fetch(FALSE);
		$this->free();
		return is_array($row) ? $row[0] : FALSE;
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
			if ($this->escMethod) {
				return "'" . pg_escape_string($this->connection, $value) . "'";
			} else {
				return "'" . pg_escape_string($value) . "'";
			}

		case dibi::FIELD_BINARY:
			if ($this->escMethod) {
				return "'" . pg_escape_bytea($this->connection, $value) . "'";
			} else {
				return "'" . pg_escape_bytea($value) . "'";
			}

		case dibi::IDENTIFIER:
			// @see http://www.postgresql.org/docs/8.2/static/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
			$a = strrpos($value, '.');
			if ($a === FALSE) {
				return '"' . str_replace('"', '""', $value) . '"';
			} else {
				// table.col delimite as table."col"
				return substr($value, 0, $a) . '."' . str_replace('"', '""', substr($value, $a + 1)) . '"';
			}

		case dibi::FIELD_BOOL:
			return $value ? 'TRUE' : 'FALSE';

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
		switch ($type) {
		case dibi::FIELD_BINARY:
			return pg_unescape_bytea($value);

		default:
			throw new InvalidArgumentException('Unsupported type.');
		}
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
		if ($limit >= 0)
			$sql .= ' LIMIT ' . (int) $limit;

		if ($offset > 0)
			$sql .= ' OFFSET ' . (int) $offset;
	}



	/********************* result set ****************d*g**/



	/**
	 * Returns the number of rows in a result set.
	 *
	 * @return int
	 */
	public function rowCount()
	{
		return pg_num_rows($this->resultSet);
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
		return pg_fetch_array($this->resultSet, NULL, $assoc ? PGSQL_ASSOC : PGSQL_NUM);
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
		return pg_result_seek($this->resultSet, $row);
	}



	/**
	 * Frees the resources allocated for this result set.
	 *
	 * @return void
	 */
	public function free()
	{
		pg_free_result($this->resultSet);
		$this->resultSet = NULL;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 *
	 * @return array
	 */
	public function getColumnsMeta()
	{
		$hasTable = version_compare(PHP_VERSION , '5.2.0', '>=');
		$count = pg_num_fields($this->resultSet);
		$res = array();
		for ($i = 0; $i < $count; $i++) {
			$row = array(
				'name'      => pg_field_name($this->resultSet, $i),
				'table'     => $hasTable ? pg_field_table($this->resultSet, $i) : NULL,
				'nativetype'=> pg_field_type($this->resultSet, $i),
			);
			$row['fullname'] = $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'];
			$res[] = $row;
		}
		return $res;
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
		$this->query("
			SELECT table_name as name, CAST(table_type = 'VIEW' AS INTEGER) as view
			FROM information_schema.tables
			WHERE table_schema = current_schema()
		");
		$res = pg_fetch_all($this->resultSet);
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
		$_table = $this->escape($table, dibi::FIELD_TEXT);
		$this->query("
			SELECT indkey
			FROM pg_index, pg_class
			WHERE pg_class.relname = $_table AND pg_class.oid = pg_index.indrelid AND pg_index.indisprimary
		");
		$primary = (int) pg_fetch_object($this->resultSet)->indkey;

		$this->query("
			SELECT *
			FROM information_schema.columns
			WHERE table_name = $_table AND table_schema = current_schema()
			ORDER BY ordinal_position
		");
		$res = array();
		while ($row = $this->fetch(TRUE)) {
			$size = (int) max($row['character_maximum_length'], $row['numeric_precision']);
			$res[] = array(
				'name' => $row['column_name'],
				'table' => $table,
				'nativetype' => strtoupper($row['udt_name']),
				'size' => $size ? $size : NULL,
				'nullable' => $row['is_nullable'] === 'YES',
				'default' => $row['column_default'],
				'autoincrement' => (int) $row['ordinal_position'] === $primary && substr($row['column_default'], 0, 7) === 'nextval',
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
