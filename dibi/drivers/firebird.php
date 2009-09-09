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
 * The dibi driver for Firebird/InterBase database.
 *
 * Connection options:
 *   - 'database' - the path to database file (server:/path/database.fdb)
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'charset' - character encoding to set
 *   - 'buffers' - buffers is the number of database buffers to allocate for the server-side cache. If 0 or omitted, server chooses its own default.
 *   - 'lazy' - if TRUE, connection will be established only when required
 *   - 'resource' - connection resource (optional)
 *
 * @author     Tomáš Kraina, Roman Sklenář
 * @copyright  Copyright (c) 2009
 * @package    dibi
 */
class DibiFirebirdDriver extends DibiObject implements IDibiDriver
{
	const ERROR_EXCEPTION_THROWN = -836;

	/** @var resource  Connection resource */
	private $connection;

	/** @var resource  Resultset resource */
	private $resultSet;

	/** @var resource  Resultset resource */
	private $transaction;

	/** @var bool */
	private $inTransaction = FALSE;


	/**
	 * @throws DibiException
	 */
	public function __construct()
	{
		if (!extension_loaded('interbase')) {
			throw new DibiDriverException("PHP extension 'interbase' is not loaded.");
		}
	}



	/**
	 * Connects to a database.
	 * @return void
	 * @throws DibiException
	 */
	public function connect(array &$config)
	{
		DibiConnection::alias($config, 'database', 'db');

		if (isset($config['resource'])) {
			$this->connection = $config['resource'];

		} else {
			// default values
			if (!isset($config['username'])) $config['username'] = ini_get('ibase.default_password');
			if (!isset($config['password'])) $config['password'] = ini_get('ibase.default_user');
			if (!isset($config['database'])) $config['database'] = ini_get('ibase.default_db');
			if (!isset($config['charset'])) $config['charset'] = ini_get('ibase.default_charset');
			if (!isset($config['buffers'])) $config['buffers'] = 0;

			DibiDriverException::tryError();
			if (empty($config['persistent'])) {
				$this->connection = ibase_connect($config['database'], $config['username'], $config['password'], $config['charset'], $config['buffers']); // intentionally @
			} else {
				$this->connection = ibase_pconnect($config['database'], $config['username'], $config['password'], $config['charset'], $config['buffers']); // intentionally @
			}
			if (DibiDriverException::catchError($msg)) {
				throw new DibiDriverException($msg, ibase_errcode());
			}

			if (!is_resource($this->connection)) {
				throw new DibiDriverException(ibase_errmsg(), ibase_errcode());
			}
		}

	}



	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		ibase_close($this->connection);
	}



	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return IDibiDriver|NULL
	 * @throws DibiDriverException|DibiException
	 */
	public function query($sql)
	{
		DibiDriverException::tryError();
		$resource = $this->inTransaction ? $this->transaction : $this->connection;
		$this->resultSet = ibase_query($resource, $sql);

		if (DibiDriverException::catchError($msg)) {
			if (ibase_errcode() == self::ERROR_EXCEPTION_THROWN) {
				preg_match('/exception (\d+) (\w+) (.*)/i', ibase_errmsg(), $match);
				throw new DibiProcedureException($match[3], $match[1], $match[2], dibi::$sql);

			} else {
				throw new DibiDriverException(ibase_errmsg(), ibase_errcode(), dibi::$sql);
			}
		}

		if ($this->resultSet === FALSE) {
			throw new DibiDriverException(ibase_errmsg(), ibase_errcode(), $sql);
		}

		return is_resource($this->resultSet) ? clone $this : NULL;
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function getAffectedRows()
	{
		return ibase_affected_rows($this->connection);
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @param  string     generator name
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function getInsertId($sequence)
	{
		return ibase_gen_id($sequence, 0, $this->connection);
		//throw new NotSupportedException('Firebird/InterBase does not support autoincrementing.');
	}



	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function begin($savepoint = NULL)
	{
		if ($savepoint !== NULL) {
			throw new DibiDriverException('Savepoints are not supported in Firebird/Interbase.');
		}
		$this->transaction = ibase_trans($this->resource);
		$this->inTransaction = TRUE;
	}



	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function commit($savepoint = NULL)
	{
		if ($savepoint !== NULL) {
			throw new DibiDriverException('Savepoints are not supported in Firebird/Interbase.');
		}

		if (!ibase_commit($this->transaction)) {
			DibiDriverException('Unable to handle operation - failure when commiting transaction.');
		}

		$this->inTransaction = FALSE;
	}



	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	public function rollback($savepoint = NULL)
	{
		if ($savepoint !== NULL) {
			throw new DibiDriverException('Savepoints are not supported in Firebird/Interbase.');
		}

		if (!ibase_rollback($this->transaction)) {
			DibiDriverException('Unable to handle operation - failure when rolbacking transaction.');
		}

		$this->inTransaction = FALSE;
	}



	/**
	 * Returns the connection resource.
	 * @return resource
	 */
	public function getResource()
	{
		return $this->connection;
	}



	/********************* SQL ********************/



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
			return $value;

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

		// see http://scott.yang.id.au/2004/01/limit-in-select-statements-in-firebird/
		$sql = 'SELECT FIRST ' . (int) $limit . ($offset > 0 ? ' SKIP ' . (int) $offset : '') . ' * FROM (' . $sql . ')';
	}



	/********************* result set ********************/



	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	public function getRowCount()
	{
		return ibase_num_fields($this->resultSet);
	}



	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 * @internal
	 */
	public function fetch($assoc)
	{
		DibiDriverException::tryError();
		$result = $assoc ? ibase_fetch_assoc($this->resultSet) : ibase_fetch_row($this->resultSet); // intentionally @

		if (DibiDriverException::catchError($msg)) {
			if (ibase_errcode() == self::ERROR_EXCEPTION_THROWN) {
				preg_match('/exception (\d+) (\w+) (.*)/i', ibase_errmsg(), $match);
				throw new DibiProcedureException($match[3], $match[1], $match[2], dibi::$sql);

			} else {
				throw new DibiDriverException($msg, ibase_errcode(), dibi::$sql);
			}
		}

		return $result;
	}



	/**
	 * Moves cursor position without fetching row.
	 * @param  int      the 0-based cursor pos to seek to
	 * @return boolean  TRUE on success, FALSE if unable to seek to specified record
	 * @throws DibiException
	 */
	public function seek($row)
	{
		throw new DibiDriverException("Firebird/Interbase do not support seek in result set.");
	}



	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	public function free()
	{
		ibase_free_result($this->resultSet);
		$this->resultSet = NULL;
	}



	/**
	 * Returns the result set resource.
	 * @return mysqli_result
	 */
	public function getResultResource()
	{
		return $this->resultSet;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getColumnsMeta()
	{
		throw new NotImplementedException;
	}



	/********************* reflection ********************/



	/**
	 * Returns list of tables.
	 * @return array
	 */
	public function getTables()
	{
		$this->query("
			SELECT TRIM(RDB\$RELATION_NAME),
				CASE RDB\$VIEW_BLR WHEN NULL THEN 'TRUE' ELSE 'FALSE' END
			FROM RDB\$RELATIONS
			WHERE RDB\$SYSTEM_FLAG = 0;"
		);
		$res = array();
		while ($row = $this->fetch(FALSE)) {
			$res[] = array(
				'name' => $row[0],
				'view' => $row[1] === 'TRUE',
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
		$table = strtoupper($table);
		$this->query("
			SELECT TRIM(r.RDB\$FIELD_NAME) AS FIELD_NAME,
				CASE f.RDB\$FIELD_TYPE
					WHEN 261 THEN 'BLOB'
					WHEN 14 THEN 'CHAR'
					WHEN 40 THEN 'CSTRING'
					WHEN 11 THEN 'D_FLOAT'
					WHEN 27 THEN 'DOUBLE'
					WHEN 10 THEN 'FLOAT'
					WHEN 16 THEN 'INT64'
					WHEN 8 THEN 'INTEGER'
					WHEN 9 THEN 'QUAD'
					WHEN 7 THEN 'SMALLINT'
					WHEN 12 THEN 'DATE'
					WHEN 13 THEN 'TIME'
					WHEN 35 THEN 'TIMESTAMP'
					WHEN 37 THEN 'VARCHAR'
					ELSE 'UNKNOWN'
				END AS FIELD_TYPE,
				f.RDB\$FIELD_LENGTH AS FIELD_LENGTH,
				r.RDB\$DEFAULT_VALUE AS DEFAULT_VALUE,
				CASE r.RDB\$NULL_FLAG
					WHEN 1 THEN 'FALSE' ELSE 'TRUE'
				END AS NULLABLE
			FROM RDB\$RELATION_FIELDS r
				LEFT JOIN RDB\$FIELDS f ON r.RDB\$FIELD_SOURCE = f.RDB\$FIELD_NAME
			WHERE r.RDB\$RELATION_NAME = '$table'
			ORDER BY r.RDB\$FIELD_POSITION;"

		);
		$res = array();
		while ($row = $this->fetch(TRUE)) {
			$key = $row['FIELD_NAME'];
			$res[$key] = array(
				'name' => $key,
				'table' => $table,
				'nativetype' => trim($row['FIELD_TYPE']),
				'size' => $row['FIELD_LENGTH'],
				'nullable' => $row['NULLABLE'] === 'TRUE',
				'default' => $row['DEFAULT_VALUE'],
				'autoincrement' => FALSE,
			);
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns metadata for all indexes in a table (the constraints are included).
	 * @param  string
	 * @return array
	 */
	public function getIndexes($table)
	{
		$table = strtoupper($table);
		$this->query("
			SELECT TRIM(s.RDB\$INDEX_NAME) AS INDEX_NAME,
				TRIM(s.RDB\$FIELD_NAME) AS FIELD_NAME,
				i.RDB\$UNIQUE_FLAG AS UNIQUE_FLAG,
				i.RDB\$FOREIGN_KEY AS FOREIGN_KEY,
				TRIM(r.RDB\$CONSTRAINT_TYPE) AS CONSTRAINT_TYPE,
				s.RDB\$FIELD_POSITION AS FIELD_POSITION
			FROM RDB\$INDEX_SEGMENTS s
				LEFT JOIN RDB\$INDICES i ON i.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
				LEFT JOIN RDB\$RELATION_CONSTRAINTS r ON r.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
			WHERE UPPER(i.RDB\$RELATION_NAME) = '$table'
			ORDER BY s.RDB\$FIELD_POSITION"
		);
		$res = array();
		while ($row = $this->fetch(TRUE)) {
			$key = $row['INDEX_NAME'];
			$res[$key]['name'] = $key;
			$res[$key]['unique'] = $row['UNIQUE_FLAG'] === 1;
			$res[$key]['primary'] = $row['CONSTRAINT_TYPE'] === 'PRIMARY KEY';
			$res[$key]['table'] = $table;
			$res[$key]['columns'][$row['FIELD_POSITION']] = $row['FIELD_NAME'];
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	public function getForeignKeys($table)
	{
		$table = strtoupper($table);
		$this->query("
			SELECT TRIM(s.RDB\$INDEX_NAME) AS INDEX_NAME,
				TRIM(s.RDB\$FIELD_NAME) AS FIELD_NAME,
			FROM RDB\$INDEX_SEGMENTS s
				LEFT JOIN RDB\$RELATION_CONSTRAINTS r ON r.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
			WHERE UPPER(i.RDB\$RELATION_NAME) = '$table'
				AND r.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'
			ORDER BY s.RDB\$FIELD_POSITION"
		);
		$res = array();
		while ($row = $this->fetch(TRUE)) {
			$key = $row['INDEX_NAME'];
			$res[$key] = array(
				'name' => $key,
				'column' => $row['FIELD_NAME'],
				'table' => $table,
			);
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns list of indices in given table (the constraints are not listed).
	 * @param  string
	 * @return array
	 */
	public function getIndices($table)
	{
		$this->query("
			SELECT TRIM(RDB\$INDEX_NAME)
			FROM RDB\$INDICES
			WHERE RDB\$RELATION_NAME = UPPER('$table')
				AND RDB\$UNIQUE_FLAG IS NULL
				AND RDB\$FOREIGN_KEY IS NULL;"
		);
		$res = array();
		while ($row = $this->fetch(FALSE)) {
			$res[] = $row[0];
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns list of constraints in given table.
	 * @param  string
	 * @return array
	 */
	public function getConstraints($table)
	{
		$this->query("
			SELECT TRIM(RDB\$INDEX_NAME)
			FROM RDB\$INDICES
			WHERE RDB\$RELATION_NAME = UPPER('$table')
				AND (
					RDB\$UNIQUE_FLAG IS NOT NULL
					OR RDB\$FOREIGN_KEY IS NOT NULL
			);"
		);
		$res = array();
		while ($row = $this->fetch(FALSE)) {
			$res[] = $row[0];
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns metadata for all triggers in a table or database.
	 * (Only if user has permissions on ALTER TABLE, INSERT/UPDATE/DELETE record in table)
	 * @param  string
	 * @param  string
	 * @return array
	 */
	public function getTriggersMeta($table = NULL)
	{
		$this->query("
			SELECT TRIM(RDB\$TRIGGER_NAME) AS TRIGGER_NAME,
				TRIM(RDB\$RELATION_NAME) AS TABLE_NAME,
				CASE RDB\$TRIGGER_TYPE
					WHEN 1 THEN 'BEFORE'
					WHEN 2 THEN 'AFTER'
					WHEN 3 THEN 'BEFORE'
					WHEN 4 THEN 'AFTER'
					WHEN 5 THEN 'BEFORE'
					WHEN 6 THEN 'AFTER'
				END AS TRIGGER_TYPE,
				CASE RDB\$TRIGGER_TYPE
					WHEN 1 THEN 'INSERT'
					WHEN 2 THEN 'INSERT'
					WHEN 3 THEN 'UPDATE'
					WHEN 4 THEN 'UPDATE'
					WHEN 5 THEN 'DELETE'
					WHEN 6 THEN 'DELETE'
				END AS TRIGGER_EVENT,
				CASE RDB\$TRIGGER_INACTIVE
					WHEN 1 THEN 'FALSE' ELSE 'TRUE'
				END AS TRIGGER_ENABLED
			FROM RDB\$TRIGGERS
			WHERE RDB\$SYSTEM_FLAG = 0"
			. ($table === NULL ? ";" : " AND RDB\$RELATION_NAME = UPPER('$table');")
		);
		$res = array();
		while ($row = $this->fetch(TRUE)) {
			$res[$row['TRIGGER_NAME']] = array(
				'name' => $row['TRIGGER_NAME'],
				'table' => $row['TABLE_NAME'],
				'type' => trim($row['TRIGGER_TYPE']),
				'event' => trim($row['TRIGGER_EVENT']),
				'enabled' => trim($row['TRIGGER_ENABLED']) === 'TRUE',
			);
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns list of triggers for given table.
	 * (Only if user has permissions on ALTER TABLE, INSERT/UPDATE/DELETE record in table)
	 * @param  string
	 * @return array
	 */
	public function getTriggers($table = NULL)
	{
		$q = "SELECT TRIM(RDB\$TRIGGER_NAME)
			FROM RDB\$TRIGGERS
			WHERE RDB\$SYSTEM_FLAG = 0";
		$q .= $table === NULL ? ";" : " AND RDB\$RELATION_NAME = UPPER('$table')";

		$this->query($q);
		$res = array();
		while ($row = $this->fetch(FALSE)) {
			$res[] = $row[0];
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns metadata from stored procedures and their input and output parameters.
	 * @param  string
	 * @return array
	 */
	public function getProceduresMeta()
	{
		$this->query("
			SELECT
				TRIM(p.RDB\$PARAMETER_NAME) AS PARAMETER_NAME,
				TRIM(p.RDB\$PROCEDURE_NAME) AS PROCEDURE_NAME,
				CASE p.RDB\$PARAMETER_TYPE
					WHEN 0 THEN 'INPUT'
					WHEN 1 THEN 'OUTPUT'
					ELSE 'UNKNOWN'
				END AS PARAMETER_TYPE,
				CASE f.RDB\$FIELD_TYPE
					WHEN 261 THEN 'BLOB'
					WHEN 14 THEN 'CHAR'
					WHEN 40 THEN 'CSTRING'
					WHEN 11 THEN 'D_FLOAT'
					WHEN 27 THEN 'DOUBLE'
					WHEN 10 THEN 'FLOAT'
					WHEN 16 THEN 'INT64'
					WHEN 8 THEN 'INTEGER'
					WHEN 9 THEN 'QUAD'
					WHEN 7 THEN 'SMALLINT'
					WHEN 12 THEN 'DATE'
					WHEN 13 THEN 'TIME'
					WHEN 35 THEN 'TIMESTAMP'
					WHEN 37 THEN 'VARCHAR'
					ELSE 'UNKNOWN'
				END AS FIELD_TYPE,
				f.RDB\$FIELD_LENGTH AS FIELD_LENGTH,
				p.RDB\$PARAMETER_NUMBER AS PARAMETER_NUMBER
			FROM RDB\$PROCEDURE_PARAMETERS p
				LEFT JOIN RDB\$FIELDS f ON f.RDB\$FIELD_NAME = p.RDB\$FIELD_SOURCE
			ORDER BY p.RDB\$PARAMETER_TYPE, p.RDB\$PARAMETER_NUMBER;"
		);
		$res = array();
		while ($row = $this->fetch(TRUE)) {
			$key = $row['PROCEDURE_NAME'];
			$io = trim($row['PARAMETER_TYPE']);
			$num = $row['PARAMETER_NUMBER'];
			$res[$key]['name'] = $row['PROCEDURE_NAME'];
			$res[$key]['params'][$io][$num]['name'] = $row['PARAMETER_NAME'];
			$res[$key]['params'][$io][$num]['type'] = trim($row['FIELD_TYPE']);
			$res[$key]['params'][$io][$num]['size'] = $row['FIELD_LENGTH'];
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns list of stored procedures.
	 * @return array
	 */
	public function getProcedures()
	{
		$this->query("
			SELECT TRIM(RDB\$PROCEDURE_NAME)
			FROM RDB\$PROCEDURES;"
		);
		$res = array();
		while ($row = $this->fetch(FALSE)) {
			$res[] = $row[0];
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns list of generators.
	 * @return array
	 */
	public function getGenerators()
	{
		$this->query("
			SELECT TRIM(RDB\$GENERATOR_NAME)
			FROM RDB\$GENERATORS
			WHERE RDB\$SYSTEM_FLAG = 0;"
		);
		$res = array();
		while ($row = $this->fetch(FALSE)) {
			$res[] = $row[0];
		}
		$this->free();
		return $res;
	}



	/**
	 * Returns list of user defined functions (UDF).
	 * @return array
	 */
	public function getFunctions()
	{
		$this->query("
			SELECT TRIM(RDB\$FUNCTION_NAME)
			FROM RDB\$FUNCTIONS
			WHERE RDB\$SYSTEM_FLAG = 0;"
		);
		$res = array();
		while ($row = $this->fetch(FALSE)) {
			$res[] = $row[0];
		}
		$this->free();
		return $res;
	}

}




/**
 * Database procedure exception.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009
 * @package    dibi
 */
class DibiProcedureException extends DibiException
{
	/** @var string */
	protected $severity;


	/**
	 * Construct the exception.
	 * @param  string  Message describing the exception
	 * @param  int     Some code
	 * @param  string SQL command
	 */
	public function __construct($message = NULL, $code = 0, $severity = NULL, $sql = NULL)
	{
		parent::__construct($message, (int) $code, $sql);
		$this->severity = $severity;
	}



	/**
	 * Gets the exception severity.
	 * @return string
	 */
	public function getSeverity()
	{
		$this->severity;
	}

}
