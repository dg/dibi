<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);


/**
 * This class is static container class for creating DB objects and
 * store connections info.
 */
class dibi
{
	use Dibi\Strict;

	public const
		AFFECTED_ROWS = 'a',
		IDENTIFIER = 'n';

	/** version */
	public const
		VERSION = '4.0-dev';

	/** sorting order */
	public const
		ASC = 'ASC',
		DESC = 'DESC';

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

	/** @var Dibi\Connection[]  Connection registry storage for DibiConnection objects */
	private static $registry = [];

	/** @var Dibi\Connection  Current connection */
	private static $connection;


	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new LogicException('Cannot instantiate static class ' . get_class($this));
	}


	/********************* connections handling ****************d*g**/


	/**
	 * Creates a new Connection object and connects it to specified database.
	 * @throws Dibi\Exception
	 */
	public static function connect($config = [], string $name = '0'): Dibi\Connection
	{
		return self::$connection = self::$registry[$name] = new Dibi\Connection($config, $name);
	}


	/**
	 * Disconnects from database (doesn't destroy Connection object).
	 */
	public static function disconnect(): void
	{
		self::getConnection()->disconnect();
	}


	/**
	 * Returns true when connection was established.
	 */
	public static function isConnected(): bool
	{
		return (self::$connection !== null) && self::$connection->isConnected();
	}


	/**
	 * Retrieve active connection.
	 * @throws Dibi\Exception
	 */
	public static function getConnection(string $name = null): Dibi\Connection
	{
		if ($name === null) {
			if (self::$connection === null) {
				throw new Dibi\Exception('Dibi is not connected to database.');
			}

			return self::$connection;
		}

		if (!isset(self::$registry[$name])) {
			throw new Dibi\Exception("There is no connection named '$name'.");
		}

		return self::$registry[$name];
	}


	/**
	 * Sets connection.
	 */
	public static function setConnection(Dibi\Connection $connection): Dibi\Connection
	{
		return self::$connection = $connection;
	}


	/********************* monostate for active connection ****************d*g**/


	/**
	 * Generates and executes SQL query - Monostate for Dibi\Connection::query().
	 * @param  mixed      one or more arguments
	 * @return Dibi\Result|int   result set or number of affected rows
	 * @throws Dibi\Exception
	 */
	public static function query(...$args)
	{
		return self::getConnection()->query($args);
	}


	/**
	 * Executes the SQL query - Monostate for Dibi\Connection::nativeQuery().
	 * @return Dibi\Result|int   result set or number of affected rows
	 */
	public static function nativeQuery(string $sql)
	{
		return self::getConnection()->nativeQuery($sql);
	}


	/**
	 * Generates and prints SQL query - Monostate for Dibi\Connection::test().
	 * @param  mixed  one or more arguments
	 */
	public static function test(...$args): bool
	{
		return self::getConnection()->test($args);
	}


	/**
	 * Generates and returns SQL query as DataSource - Monostate for Dibi\Connection::test().
	 * @param  mixed      one or more arguments
	 */
	public static function dataSource(...$args): Dibi\DataSource
	{
		return self::getConnection()->dataSource($args);
	}


	/**
	 * Executes SQL query and fetch result - Monostate for Dibi\Connection::query() & fetch().
	 * @param  mixed    one or more arguments
	 * @return Dibi\Row|NULL
	 * @throws Dibi\Exception
	 */
	public static function fetch(...$args)
	{
		return self::getConnection()->query($args)->fetch();
	}


	/**
	 * Executes SQL query and fetch results - Monostate for Dibi\Connection::query() & fetchAll().
	 * @param  mixed    one or more arguments
	 * @return Dibi\Row[]
	 * @throws Dibi\Exception
	 */
	public static function fetchAll(...$args): array
	{
		return self::getConnection()->query($args)->fetchAll();
	}


	/**
	 * Executes SQL query and fetch first column - Monostate for Dibi\Connection::query() & fetchSingle().
	 * @param  mixed    one or more arguments
	 * @return mixed
	 * @throws Dibi\Exception
	 */
	public static function fetchSingle(...$args)
	{
		return self::getConnection()->query($args)->fetchSingle();
	}


	/**
	 * Executes SQL query and fetch pairs - Monostate for Dibi\Connection::query() & fetchPairs().
	 * @param  mixed    one or more arguments
	 * @throws Dibi\Exception
	 */
	public static function fetchPairs(...$args): array
	{
		return self::getConnection()->query($args)->fetchPairs();
	}


	/**
	 * Gets the number of affected rows.
	 * Monostate for Dibi\Connection::getAffectedRows()
	 * @throws Dibi\Exception
	 */
	public static function getAffectedRows(): int
	{
		return self::getConnection()->getAffectedRows();
	}


	/**
	 * @deprecated
	 */
	public static function affectedRows(): int
	{
		trigger_error(__METHOD__ . '() is deprecated, use getAffectedRows()', E_USER_DEPRECATED);
		return self::getConnection()->getAffectedRows();
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * Monostate for Dibi\Connection::getInsertId()
	 * @throws Dibi\Exception
	 */
	public static function getInsertId(string $sequence = null): int
	{
		return self::getConnection()->getInsertId($sequence);
	}


	/**
	 * @deprecated
	 */
	public static function insertId(string $sequence = null): int
	{
		trigger_error(__METHOD__ . '() is deprecated, use getInsertId()', E_USER_DEPRECATED);
		return self::getConnection()->getInsertId($sequence);
	}


	/**
	 * Begins a transaction - Monostate for Dibi\Connection::begin().
	 * @throws Dibi\Exception
	 */
	public static function begin(string $savepoint = null): void
	{
		self::getConnection()->begin($savepoint);
	}


	/**
	 * Commits statements in a transaction - Monostate for Dibi\Connection::commit($savepoint = null).
	 * @throws Dibi\Exception
	 */
	public static function commit(string $savepoint = null): void
	{
		self::getConnection()->commit($savepoint);
	}


	/**
	 * Rollback changes in a transaction - Monostate for Dibi\Connection::rollback().
	 * @throws Dibi\Exception
	 */
	public static function rollback(string $savepoint = null): void
	{
		self::getConnection()->rollback($savepoint);
	}


	/**
	 * Gets a information about the current database - Monostate for Dibi\Connection::getDatabaseInfo().
	 */
	public static function getDatabaseInfo(): Dibi\Reflection\Database
	{
		return self::getConnection()->getDatabaseInfo();
	}


	/**
	 * Import SQL dump from file - extreme fast!
	 * @return int  count of sql commands
	 */
	public static function loadFile(string $file): int
	{
		return Dibi\Helpers::loadFromFile(self::getConnection(), $file);
	}


	/********************* fluent SQL builders ****************d*g**/


	public static function command(): Dibi\Fluent
	{
		return self::getConnection()->command();
	}


	public static function select(...$args): Dibi\Fluent
	{
		return self::getConnection()->select(...$args);
	}


	public static function update(string $table, array $args): Dibi\Fluent
	{
		return self::getConnection()->update($table, $args);
	}


	public static function insert(string $table, array $args): Dibi\Fluent
	{
		return self::getConnection()->insert($table, $args);
	}


	public static function delete(string $table): Dibi\Fluent
	{
		return self::getConnection()->delete($table);
	}


	/********************* substitutions ****************d*g**/


	/**
	 * Returns substitution hashmap - Monostate for Dibi\Connection::getSubstitutes().
	 */
	public static function getSubstitutes(): Dibi\HashMap
	{
		return self::getConnection()->getSubstitutes();
	}


	/********************* misc tools ****************d*g**/


	/**
	 * Prints out a syntax highlighted version of the SQL command or Result.
	 * @param  string|Result
	 * @param  bool  return output instead of printing it?
	 */
	public static function dump($sql = null, bool $return = false): ?string
	{
		return Dibi\Helpers::dump($sql, $return);
	}
}
