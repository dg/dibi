<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


/**
 * This class is static container class for creating DB objects and
 * store connections info.
 *
 * @package    dibi
 */
class dibi
{
	/** column type */
	const TEXT = 's', // as 'string'
		BINARY = 'bin',
		BOOL = 'b',
		INTEGER = 'i',
		FLOAT = 'f',
		DATE = 'd',
		DATETIME = 't',
		TIME = 't';

	const IDENTIFIER = 'n',
		AFFECTED_ROWS = 'a';

	/** @deprecated */
	const FIELD_TEXT = self::TEXT,
		FIELD_BINARY = self::BINARY,
		FIELD_BOOL = self::BOOL,
		FIELD_INTEGER = self::INTEGER,
		FIELD_FLOAT = self::FLOAT,
		FIELD_DATE = self::DATE,
		FIELD_DATETIME = self::DATETIME,
		FIELD_TIME = self::TIME;

	/** version */
	const VERSION = '2.3.2',
		REVISION = 'released on 2015-04-18';

	/** sorting order */
	const ASC = 'ASC',
		DESC = 'DESC';

	/** @var DibiConnection[]  Connection registry storage for DibiConnection objects */
	private static $registry = [];

	/** @var DibiConnection  Current connection */
	private static $connection;

	/** @var string  Last SQL command @see dibi::query() */
	public static $sql;

	/** @var int  Elapsed time for last query */
	public static $elapsedTime;

	/** @var int  Elapsed time for all queries */
	public static $totalTime;

	/** @var int  Number or queries */
	public static $numOfQueries = 0;

	/** @var string  Default dibi driver */
	public static $defaultDriver = 'mysqli';


	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new LogicException('Cannot instantiate static class ' . get_class($this));
	}


	/********************* connections handling ****************d*g**/


	/**
	 * Creates a new DibiConnection object and connects it to specified database.
	 * @param  mixed   connection parameters
	 * @param  string  connection name
	 * @return DibiConnection
	 * @throws DibiException
	 */
	public static function connect($config = [], $name = 0)
	{
		return self::$connection = self::$registry[$name] = new DibiConnection($config, $name);
	}


	/**
	 * Disconnects from database (doesn't destroy DibiConnection object).
	 * @return void
	 */
	public static function disconnect()
	{
		self::getConnection()->disconnect();
	}


	/**
	 * Returns TRUE when connection was established.
	 * @return bool
	 */
	public static function isConnected()
	{
		return (self::$connection !== NULL) && self::$connection->isConnected();
	}


	/**
	 * Retrieve active connection.
	 * @param  string   connection registy name
	 * @return DibiConnection
	 * @throws DibiException
	 */
	public static function getConnection($name = NULL)
	{
		if ($name === NULL) {
			if (self::$connection === NULL) {
				throw new DibiException('Dibi is not connected to database.');
			}

			return self::$connection;
		}

		if (!isset(self::$registry[$name])) {
			throw new DibiException("There is no connection named '$name'.");
		}

		return self::$registry[$name];
	}


	/**
	 * Sets connection.
	 * @param  DibiConnection
	 * @return DibiConnection
	 */
	public static function setConnection(DibiConnection $connection)
	{
		return self::$connection = $connection;
	}


	/**
	 * @deprecated
	 */
	public static function activate($name)
	{
		trigger_error(__METHOD__ . '() is deprecated.', E_USER_DEPRECATED);
		self::$connection = self::getConnection($name);
	}


	/********************* monostate for active connection ****************d*g**/


	/**
	 * Generates and executes SQL query - Monostate for DibiConnection::query().
	 * @param  array|mixed      one or more arguments
	 * @return DibiResult|int   result set object (if any)
	 * @throws DibiException
	 */
	public static function query($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args);
	}


	/**
	 * Executes the SQL query - Monostate for DibiConnection::nativeQuery().
	 * @param  string           SQL statement.
	 * @return DibiResult|int   result set object (if any)
	 */
	public static function nativeQuery($sql)
	{
		return self::getConnection()->nativeQuery($sql);
	}


	/**
	 * Generates and prints SQL query - Monostate for DibiConnection::test().
	 * @param  array|mixed  one or more arguments
	 * @return bool
	 */
	public static function test($args)
	{
		$args = func_get_args();
		return self::getConnection()->test($args);
	}


	/**
	 * Generates and returns SQL query as DibiDataSource - Monostate for DibiConnection::test().
	 * @param  array|mixed      one or more arguments
	 * @return DibiDataSource
	 */
	public static function dataSource($args)
	{
		$args = func_get_args();
		return self::getConnection()->dataSource($args);
	}


	/**
	 * Executes SQL query and fetch result - Monostate for DibiConnection::query() & fetch().
	 * @param  array|mixed    one or more arguments
	 * @return DibiRow
	 * @throws DibiException
	 */
	public static function fetch($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args)->fetch();
	}


	/**
	 * Executes SQL query and fetch results - Monostate for DibiConnection::query() & fetchAll().
	 * @param  array|mixed    one or more arguments
	 * @return DibiRow[]
	 * @throws DibiException
	 */
	public static function fetchAll($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args)->fetchAll();
	}


	/**
	 * Executes SQL query and fetch first column - Monostate for DibiConnection::query() & fetchSingle().
	 * @param  array|mixed    one or more arguments
	 * @return string
	 * @throws DibiException
	 */
	public static function fetchSingle($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args)->fetchSingle();
	}


	/**
	 * Executes SQL query and fetch pairs - Monostate for DibiConnection::query() & fetchPairs().
	 * @param  array|mixed    one or more arguments
	 * @return string
	 * @throws DibiException
	 */
	public static function fetchPairs($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args)->fetchPairs();
	}


	/**
	 * Gets the number of affected rows.
	 * Monostate for DibiConnection::getAffectedRows()
	 * @return int  number of rows
	 * @throws DibiException
	 */
	public static function getAffectedRows()
	{
		return self::getConnection()->getAffectedRows();
	}


	/**
	 * Gets the number of affected rows. Alias for getAffectedRows().
	 * @return int  number of rows
	 * @throws DibiException
	 */
	public static function affectedRows()
	{
		return self::getConnection()->getAffectedRows();
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * Monostate for DibiConnection::getInsertId()
	 * @param  string     optional sequence name
	 * @return int
	 * @throws DibiException
	 */
	public static function getInsertId($sequence = NULL)
	{
		return self::getConnection()->getInsertId($sequence);
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column. Alias for getInsertId().
	 * @param  string     optional sequence name
	 * @return int
	 * @throws DibiException
	 */
	public static function insertId($sequence = NULL)
	{
		return self::getConnection()->getInsertId($sequence);
	}


	/**
	 * Begins a transaction - Monostate for DibiConnection::begin().
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiException
	 */
	public static function begin($savepoint = NULL)
	{
		self::getConnection()->begin($savepoint);
	}


	/**
	 * Commits statements in a transaction - Monostate for DibiConnection::commit($savepoint = NULL).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiException
	 */
	public static function commit($savepoint = NULL)
	{
		self::getConnection()->commit($savepoint);
	}


	/**
	 * Rollback changes in a transaction - Monostate for DibiConnection::rollback().
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiException
	 */
	public static function rollback($savepoint = NULL)
	{
		self::getConnection()->rollback($savepoint);
	}


	/**
	 * Gets a information about the current database - Monostate for DibiConnection::getDatabaseInfo().
	 * @return DibiDatabaseInfo
	 */
	public static function getDatabaseInfo()
	{
		return self::getConnection()->getDatabaseInfo();
	}


	/**
	 * Import SQL dump from file - extreme fast!
	 * @param  string  filename
	 * @return int  count of sql commands
	 */
	public static function loadFile($file)
	{
		return self::getConnection()->loadFile($file);
	}


	/**
	 * Replacement for majority of dibi::methods() in future.
	 */
	public static function __callStatic($name, $args)
	{
		//if ($name = 'select', 'update', ...') {
		// return self::command()->$name($args);
		//}
		return call_user_func_array([self::getConnection(), $name], $args);
	}


	/********************* fluent SQL builders ****************d*g**/


	/**
	 * @return DibiFluent
	 */
	public static function command()
	{
		return self::getConnection()->command();
	}


	/**
	 * @param  string    column name
	 * @return DibiFluent
	 */
	public static function select($args)
	{
		$args = func_get_args();
		return call_user_func_array([self::getConnection(), 'select'], $args);
	}


	/**
	 * @param  string   table
	 * @param  array
	 * @return DibiFluent
	 */
	public static function update($table, $args)
	{
		return self::getConnection()->update($table, $args);
	}


	/**
	 * @param  string   table
	 * @param  array
	 * @return DibiFluent
	 */
	public static function insert($table, $args)
	{
		return self::getConnection()->insert($table, $args);
	}


	/**
	 * @param  string   table
	 * @return DibiFluent
	 */
	public static function delete($table)
	{
		return self::getConnection()->delete($table);
	}


	/********************* substitutions ****************d*g**/


	/**
	 * Returns substitution hashmap - Monostate for DibiConnection::getSubstitutes().
	 * @return DibiHashMap
	 */
	public static function getSubstitutes()
	{
		return self::getConnection()->getSubstitutes();
	}


	/********************* misc tools ****************d*g**/


	/**
	 * Prints out a syntax highlighted version of the SQL command or DibiResult.
	 * @param  string|DibiResult
	 * @param  bool  return output instead of printing it?
	 * @return string
	 */
	public static function dump($sql = NULL, $return = FALSE)
	{
		return DibiHelpers::dump($sql, $return);
	}

}
