<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);


/**
 * This class is static container class for creating DB objects and
 * store connections info.
 *
 * @method void disconnect()
 * @method Dibi\Result|int query(...$args)
 * @method Dibi\Result|int nativeQuery(...$args)
 * @method bool test(...$args)
 * @method Dibi\DataSource dataSource(...$args)
 * @method Dibi\Row|null fetch(...$args)
 * @method array fetchAll(...$args)
 * @method mixed fetchSingle(...$args)
 * @method array fetchPairs(...$args)
 * @method int getAffectedRows()
 * @method int getInsertId(string $sequence = null)
 * @method void begin(string $savepoint = null)
 * @method void commit(string $savepoint = null)
 * @method void rollback(string $savepoint = null)
 * @method Dibi\Reflection\Database getDatabaseInfo()
 * @method Dibi\Fluent command()
 * @method Dibi\Fluent select(...$args)
 * @method Dibi\Fluent update(string $table, array $args)
 * @method Dibi\Fluent insert(string $table, array $args)
 * @method Dibi\Fluent delete(string $table)
 * @method Dibi\HashMap getSubstitutes()
 * @method int loadFile(string $file)
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

	/** @var string|null  Last SQL command @see dibi::query() */
	public static $sql;

	/** @var float|null  Elapsed time for last query */
	public static $elapsedTime;

	/** @var float  Elapsed time for all queries */
	public static $totalTime;

	/** @var int  Number or queries */
	public static $numOfQueries = 0;

	/** @var Dibi\Connection[]  Connection registry storage for Dibi\Connection objects */
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
	 * Monostate for Dibi\Connection.
	 */
	public static function __callStatic($name, $args)
	{
		return self::getConnection()->$name(...$args);
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
	 * @deprecated
	 */
	public static function insertId(string $sequence = null): int
	{
		trigger_error(__METHOD__ . '() is deprecated, use getInsertId()', E_USER_DEPRECATED);
		return self::getConnection()->getInsertId($sequence);
	}


	/********************* misc tools ****************d*g**/


	/**
	 * Prints out a syntax highlighted version of the SQL command or Result.
	 * @param  string|Dibi\Result  $sql
	 * @param  bool  $return  return output instead of printing it?
	 */
	public static function dump($sql = null, bool $return = false): ?string
	{
		return Dibi\Helpers::dump($sql, $return);
	}
}
