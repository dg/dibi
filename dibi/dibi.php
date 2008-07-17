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
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @version    0.9 (Revision: $WCREV$, Date: $WCDATE$)
 * @link       http://dibiphp.com
 * @package    dibi
 * @version    $Id$
 */


/**
 * Check PHP configuration.
 */
if (version_compare(PHP_VERSION, '5.1.0', '<')) {
	throw new Exception('dibi needs PHP 5.1.0 or newer.');
}




/**
 * Compatibility with Nette
 */
if (!class_exists('NotImplementedException', FALSE)) {
	class NotImplementedException extends LogicException {}
}

if (!class_exists('NotSupportedException', FALSE)) {
	class NotSupportedException extends LogicException {}
}

if (!class_exists('MemberAccessException', FALSE)) {
	class MemberAccessException extends LogicException {}
}

if (!class_exists('InvalidStateException', FALSE)) {
	class InvalidStateException extends RuntimeException {}
}

if (!class_exists('IOException', FALSE)) {
	class IOException extends RuntimeException {}
}

if (!class_exists('FileNotFoundException', FALSE)) {
	class FileNotFoundException extends IOException {}
}

if (!class_exists(/*Nette::*/'Object', FALSE)) {
	require_once dirname(__FILE__) . '/Nette/Object.php';
}

if (!interface_exists(/*Nette::*/'IDebuggable', FALSE)) {
	require_once dirname(__FILE__) . '/Nette/IDebuggable.php';
}

// dibi libraries
require_once dirname(__FILE__) . '/libs/interfaces.php';
require_once dirname(__FILE__) . '/libs/DibiException.php';
require_once dirname(__FILE__) . '/libs/DibiConnection.php';
require_once dirname(__FILE__) . '/libs/DibiResult.php';
require_once dirname(__FILE__) . '/libs/DibiTranslator.php';
require_once dirname(__FILE__) . '/libs/DibiVariable.php';
require_once dirname(__FILE__) . '/libs/DibiTable.php';
require_once dirname(__FILE__) . '/libs/DibiDataSource.php';
require_once dirname(__FILE__) . '/libs/DibiFluent.php';





/**
 * Interface for database drivers.
 *
 * This class is static container class for creating DB objects and
 * store connections info.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 */
class dibi
{
	/**
	 * Column type in relation to PHP native type.
	 */
	const
		FIELD_TEXT =     's', // as 'string'
		FIELD_BINARY =   'bin',
		FIELD_BOOL =     'b',
		FIELD_INTEGER =  'i',
		FIELD_FLOAT =    'f',
		FIELD_DATE =     'd',
		FIELD_DATETIME = 't',

		// special
		IDENTIFIER =     'n';

	/**
	 * dibi version
	 */
	const
		VERSION = '0.9',
		REVISION = '$WCREV$ released on $WCDATE$';


	/**
	 * Connection registry storage for DibiConnection objects.
	 * @var DibiConnection[]
	 */
	private static $registry = array();

	/**
	 * Current connection.
	 * @var DibiConnection
	 */
	private static $connection;

	/**
	 * Substitutions for identifiers.
	 * @var array
	 */
	private static $substs = array();

	/**
	 * Substitution fallback.
	 * @var callback
	 */
	private static $substFallBack;

	/**
	 * @see addHandler
	 * @var array
	 */
	private static $handlers = array();

	/**
	 * Last SQL command @see dibi::query()
	 * @var string
	 */
	public static $sql;

	/**
	 * Elapsed time for last query.
	 * @var int
	 */
	public static $elapsedTime;

	/**
	 * Elapsed time for all queries.
	 * @var int
	 */
	public static $totalTime;

	/**
	 * Number or queries.
	 * @var int
	 */
	public static $numOfQueries = 0;

	/**
	 * Default dibi driver.
	 * @var string
	 */
	public static $defaultDriver = 'mysql';






	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new LogicException("Cannot instantiate static class " . get_class($this));
	}



	/********************* connections handling ****************d*g**/



	/**
	 * Creates a new DibiConnection object and connects it to specified database.
	 *
	 * @param  array|string|Nette::Collections::Hashtable connection parameters
	 * @param  string       connection name
	 * @return DibiConnection
	 * @throws DibiException
	 */
	public static function connect($config = array(), $name = 0)
	{
		return self::$connection = self::$registry[$name] = new DibiConnection($config, $name);
	}



	/**
	 * Disconnects from database (doesn't destroy DibiConnection object).
	 *
	 * @return void
	 */
	public static function disconnect()
	{
		self::getConnection()->disconnect();
	}



	/**
	 * Returns TRUE when connection was established.
	 *
	 * @return bool
	 */
	public static function isConnected()
	{
		return (self::$connection !== NULL) && self::$connection->isConnected();
	}



	/**
	 * Retrieve active connection.
	 *
	 * @param  string   connection registy name
	 * @return object   DibiConnection object.
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
	 * Change active connection.
	 *
	 * @param  string   connection registy name
	 * @return void
	 * @throws DibiException
	 */
	public static function activate($name)
	{
		self::$connection = self::getConnection($name);
	}



	/********************* monostate for active connection ****************d*g**/



	/**
	 * Generates and executes SQL query - Monostate for DibiConnection::query().
	 *
	 * @param  array|mixed      one or more arguments
	 * @return DibiResult|NULL  result set object (if any)
	 * @throws DibiException
	 */
	public static function query($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args);
	}



	/**
	 * Executes the SQL query - Monostate for DibiConnection::nativeQuery().
	 *
	 * @param  string           SQL statement.
	 * @return DibiResult|NULL  result set object (if any)
	 */
	public static function nativeQuery($sql)
	{
		return self::getConnection()->nativeQuery($sql);
	}



	/**
	 * Generates and prints SQL query - Monostate for DibiConnection::test().
	 *
	 * @param  array|mixed  one or more arguments
	 * @return bool
	 */
	public static function test($args)
	{
		$args = func_get_args();
		return self::getConnection()->test($args);
	}



	/**
	 * Executes SQL query and fetch result - Monostate for DibiConnection::query() & fetch().
	 *
	 * @param  array|mixed    one or more arguments
	 * @return array
	 * @throws DibiException
	 */
	public static function fetch($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args)->fetch();
	}



	/**
	 * Executes SQL query and fetch results - Monostate for DibiConnection::query() & fetchAll().
	 *
	 * @param  array|mixed    one or more arguments
	 * @return array
	 * @throws DibiException
	 */
	public static function fetchAll($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args)->fetchAll();
	}



	/**
	 * Executes SQL query and fetch first column - Monostate for DibiConnection::query() & fetchSingle().
	 *
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
	 * Gets the number of affected rows.
	 * Monostate for DibiConnection::affectedRows()
	 *
	 * @return int  number of rows
	 * @throws DibiException
	 */
	public static function affectedRows()
	{
		return self::getConnection()->affectedRows();
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * Monostate for DibiConnection::insertId()
	 *
	 * @param  string     optional sequence name
	 * @return int
	 * @throws DibiException
	 */
	public static function insertId($sequence=NULL)
	{
		return self::getConnection()->insertId($sequence);
	}



	/**
	 * Begins a transaction - Monostate for DibiConnection::begin().
	 * @return void
	 * @throws DibiException
	 */
	public static function begin()
	{
		self::getConnection()->begin();
	}



	/**
	 * Commits statements in a transaction - Monostate for DibiConnection::commit().
	 * @return void
	 * @throws DibiException
	 */
	public static function commit()
	{
		self::getConnection()->commit();
	}



	/**
	 * Rollback changes in a transaction - Monostate for DibiConnection::rollback().
	 * @return void
	 * @throws DibiException
	 */
	public static function rollback()
	{
		self::getConnection()->rollback();
	}



	/**
	 * Import SQL dump from file - extreme fast!
	 *
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
	protected static function __callStatic($name, $args)
	{
		//if ($name = 'select', 'update', ...') {
		//	return self::command()->$name($args);
		//}
		return call_user_func_array(array(self::getConnection(), $name), $args);
	}



	/********************* fluent SQL builders ****************d*g**/



	/**
	 * @return DibiFluent
	 */
	public static function command()
	{
		return new DibiFluent(self::getConnection());
	}



	/**
	 * @param  string    column name
	 * @return DibiFluent
	 */
	public static function select($args)
	{
		$args = func_get_args();
		return self::command()->__call('select', $args);
	}



	/**
	 * @param  string   table
	 * @param  array
	 * @return DibiFluent
	 */
	public static function update($table, array $args)
	{
		return self::command()->update('%n', $table)->set($args);
	}



	/**
	 * @param  string   table
	 * @param  array
	 * @return DibiFluent
	 */
	public static function insert($table, array $args)
	{
		return self::command()->insert()
			->into('%n', $table, '(%n)', array_keys($args))->values('%l', array_values($args));
	}



	/**
	 * @param  string   table
	 * @return DibiFluent
	 */
	public static function delete($table)
	{
		return self::command()->delete()->from('%n', $table);
	}



	/********************* data types ****************d*g**/



	/**
	 * Pseudotype for timestamp representation.
	 *
	 * @param  mixed  datetime
	 * @return DibiVariable
	 */
	public static function datetime($time = NULL)
	{
		if ($time === NULL) {
			$time = time(); // current time
		} elseif (is_string($time)) {
			$time = strtotime($time); // try convert to timestamp
		} else {
			$time = (int) $time;
		}
		return new DibiVariable($time, dibi::FIELD_DATETIME);
	}



	/**
	 * Pseudotype for date representation.
	 *
	 * @param  mixed  date
	 * @return DibiVariable
	 */
	public static function date($date = NULL)
	{
		$var = self::datetime($date);
		$var->modifier = dibi::FIELD_DATE;
		return $var;
	}



	/********************* substitutions ****************d*g**/



	/**
	 * Create a new substitution pair for indentifiers.
	 *
	 * @param  string from
	 * @param  string to
	 * @return void
	 */
	public static function addSubst($expr, $subst)
	{
		self::$substs[$expr] = $subst;
	}



	/**
	 * Sets substitution fallback handler.
	 *
	 * @param  callback
	 * @return void
	 */
	public static function setSubstFallback($callback)
	{
		if (!is_callable($callback)) {
			throw new InvalidArgumentException("Invalid callback.");
		}

		self::$substFallBack = $callback;
	}



	/**
	 * Remove substitution pair.
	 *
	 * @param  mixed from or TRUE
	 * @return void
	 */
	public static function removeSubst($expr)
	{
		if ($expr === TRUE) {
			self::$substs = array();
		} else {
			unset(self::$substs[':'.$expr.':']);
		}
	}



	/**
	 * Provides substitution.
	 *
	 * @param  string
	 * @return string
	 */
	public static function substitute($value)
	{
		if (strpos($value, ':') === FALSE) {
			return $value;

		} else {
			return preg_replace_callback('#:(.*):#U', array('dibi', 'subCb'), $value);
		}
	}



	/**
	 * Substitution callback.
	 *
	 * @param  array
	 * @return string
	 */
	private static function subCb($m)
	{
		$m = $m[1];
		if (isset(self::$substs[$m])) {
			return self::$substs[$m];

		} elseif (self::$substFallBack) {
			return self::$substs[$m] = call_user_func(self::$substFallBack, $m);

		} else {
			return $m;
		}
	}



	/********************* event handling ****************d*g**/



	/**
	 * Add new event handler.
	 *
	 * @param  callback
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public static function addHandler($callback)
	{
		if (!is_callable($callback)) {
			throw new InvalidArgumentException("Invalid callback.");
		}

		self::$handlers[] = $callback;
	}



	/**
	 * Event notification (events: exception, connected, beforeQuery, afterQuery, begin, commit, rollback).
	 *
	 * @param  DibiConnection
	 * @param  string event name
	 * @param  mixed
	 * @return void
	 */
	public static function notify(DibiConnection $connection = NULL, $event, $arg = NULL)
	{
		foreach (self::$handlers as $handler) {
			call_user_func($handler, $connection, $event, $arg);
		}
	}



	/**
	 * Enable profiler & logger.
	 *
	 * @param  string  filename
	 * @param  bool    log all queries?
	 * @return DibiProfiler
	 */
	public static function startLogger($file, $logQueries = FALSE)
	{
		require_once dirname(__FILE__) . '/libs/DibiLogger.php';

		$logger = new DibiLogger($file);
		$logger->logQueries = $logQueries;
		self::addHandler(array($logger, 'handler'));
		return $logger;
	}



	/********************* misc tools ****************d*g**/



	/**
	 * Prints out a syntax highlighted version of the SQL command or DibiResult.
	 *
	 * @param  string|DibiResult
	 * @param  bool  return output instead of printing it?
	 * @return string
	 */
	public static function dump($sql = NULL, $return = FALSE)
	{
		ob_start();
		if ($sql instanceof DibiResult) {
			$sql->dump();

		} else {
			if ($sql === NULL) $sql = self::$sql;

			static $keywords1 = 'SELECT|UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';
			static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|TRUE|FALSE';

			// insert new lines
			$sql = ' ' . $sql;
			$sql = preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

			// reduce spaces
			$sql = preg_replace('#[ \t]{2,}#', " ", $sql);

			$sql = wordwrap($sql, 100);
			$sql = htmlSpecialChars($sql);
			$sql = preg_replace("#\n{2,}#", "\n", $sql);

			// syntax highlight
			$sql = preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is", array('dibi', 'highlightCallback'), $sql);
			$sql = trim($sql);
			echo '<pre class="dump">', $sql, "</pre>\n";
		}

		if ($return) {
			return ob_get_clean();
		} else {
			ob_end_flush();
		}
	}



	private static function highlightCallback($matches)
	{
		if (!empty($matches[1])) // comment
			return '<em style="color:gray">' . $matches[1] . '</em>';

		if (!empty($matches[2])) // error
			return '<strong style="color:red">' . $matches[2] . '</strong>';

		if (!empty($matches[3])) // most important keywords
			return '<strong style="color:blue">' . $matches[3] . '</strong>';

		if (!empty($matches[4])) // other keywords
			return '<strong style="color:green">' . $matches[4] . '</strong>';
	}



	/**
	 * Returns brief descriptions.
	 * @return string
	 * @return array
	 */
	public static function getColophon($sender = NULL)
	{
		$arr = array(
			'Number of SQL queries: ' . dibi::$numOfQueries
			. (dibi::$totalTime === NULL ? '' : ', elapsed time: ' . sprintf('%0.3f', dibi::$totalTime * 1000) . ' ms'),
		);
		if ($sender === 'bluescreen') {
			$arr[] = 'dibi ' . dibi::VERSION . ' (revision ' . dibi::REVISION . ')';
		}
		return $arr;
	}

}
