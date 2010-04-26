<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2010 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt, and/or GPL license.
 *
 * For more information please see http://dibiphp.com
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */


/**
 * Check PHP configuration.
 */
if (version_compare(PHP_VERSION, '5.2.0', '<')) {
	throw new Exception('dibi needs PHP 5.2.0 or newer.');
}

@set_magic_quotes_runtime(FALSE); // intentionally @



/**
 * Compatibility with Nette
 */
if (!class_exists('NotImplementedException', FALSE)) {
	/** @package exceptions */
	class NotImplementedException extends LogicException {}
}

if (!class_exists('NotSupportedException', FALSE)) {
	/** @package exceptions */
	class NotSupportedException extends LogicException {}
}

if (!class_exists('MemberAccessException', FALSE)) {
	/** @package exceptions */
	class MemberAccessException extends LogicException {}
}

if (!class_exists('InvalidStateException', FALSE)) {
	/** @package exceptions */
	class InvalidStateException extends RuntimeException {}
}

if (!class_exists('IOException', FALSE)) {
	/** @package exceptions */
	class IOException extends RuntimeException {}
}

if (!class_exists('FileNotFoundException', FALSE)) {
	/** @package exceptions */
	class FileNotFoundException extends IOException {}
}

if (!interface_exists(/*Nette\*/'IDebugPanel', FALSE)) {
	require_once dirname(__FILE__) . '/Nette/IDebugPanel.php';
}

if (!class_exists('DateTime53', FALSE)) {
	require_once dirname(__FILE__) . '/Nette/DateTime53.php';
}



/**
 * @deprecated
 */
class DibiVariable extends DateTime53
{
	function __construct($val)
	{
		parent::__construct($val);
	}
}



// dibi libraries
require_once dirname(__FILE__) . '/libs/interfaces.php';
require_once dirname(__FILE__) . '/libs/DibiObject.php';
require_once dirname(__FILE__) . '/libs/DibiException.php';
require_once dirname(__FILE__) . '/libs/DibiConnection.php';
require_once dirname(__FILE__) . '/libs/DibiResult.php';
require_once dirname(__FILE__) . '/libs/DibiResultIterator.php';
require_once dirname(__FILE__) . '/libs/DibiRow.php';
require_once dirname(__FILE__) . '/libs/DibiTranslator.php';
require_once dirname(__FILE__) . '/libs/DibiDataSource.php';
require_once dirname(__FILE__) . '/libs/DibiFluent.php';
require_once dirname(__FILE__) . '/libs/DibiDatabaseInfo.php';
require_once dirname(__FILE__) . '/libs/DibiProfiler.php';





/**
 * Interface for database drivers.
 *
 * This class is static container class for creating DB objects and
 * store connections info.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi
 */
class dibi
{
	/**#@+
	 * dibi data type
	 */
	const TEXT =       's'; // as 'string'
	const BINARY =     'bin';
	const BOOL =       'b';
	const INTEGER =    'i';
	const FLOAT =      'f';
	const DATE =       'd';
	const DATETIME =   't';
	const TIME =       't';
	const IDENTIFIER = 'n';
	/**#@-*/

	/**#@+
	 * @deprecated column types
	 */
	const FIELD_TEXT = self::TEXT;
	const FIELD_BINARY = self::BINARY;
	const FIELD_BOOL = self::BOOL;
	const FIELD_INTEGER = self::INTEGER;
	const FIELD_FLOAT = self::FLOAT;
	const FIELD_DATE = self::DATE;
	const FIELD_DATETIME = self::DATETIME;
	const FIELD_TIME = self::TIME;
	/**#@-*/

	/**#@+
	 * dibi version
	 */
	const VERSION = '1.3-dev';
	const REVISION = '$WCREV$ released on $WCDATE$';
	/**#@-*/

	/**#@+
	 * Configuration options
	 */
	const RESULT_DETECT_TYPES = 'resultDetectTypes';
	const RESULT_DATE_TIME = 'resultDateTime';
	const ASC = 'ASC', DESC = 'DESC';
	/**#@-*/

	/** @var DibiConnection[]  Connection registry storage for DibiConnection objects */
	private static $registry = array();

	/** @var DibiConnection  Current connection */
	private static $connection;

	/** @var array  Substitutions for identifiers */
	public static $substs = array();

	/** @var callback  Substitution fallback */
	public static $substFallBack = array(__CLASS__, 'defaultSubstFallback');

	/** @var array  @see addHandler */
	private static $handlers = array();

	/** @var string  Last SQL command @see dibi::query() */
	public static $sql;

	/** @var int  Elapsed time for last query */
	public static $elapsedTime;

	/** @var int  Elapsed time for all queries */
	public static $totalTime;

	/** @var int  Number or queries */
	public static $numOfQueries = 0;

	/** @var string  Default dibi driver */
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
	 * @param  mixed   connection parameters
	 * @param  string  connection name
	 * @return DibiConnection
	 * @throws DibiException
	 */
	public static function connect($config = array(), $name = 0)
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
	 * Change active connection.
	 * @param  string   connection registy name
	 * @return void
	 * @throws DibiException
	 */
	public static function activate($name)
	{
		self::$connection = self::getConnection($name);
	}



	/**
	 * Retrieve active connection profiler.
	 * @return IDibiProfiler
	 * @throws DibiException
	 */
	public static function getProfiler()
	{
		return self::getConnection()->getProfiler();
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
	 * @return array of DibiRow
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
	public static function getInsertId($sequence=NULL)
	{
		return self::getConnection()->getInsertId($sequence);
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column. Alias for getInsertId().
	 * @param  string     optional sequence name
	 * @return int
	 * @throws DibiException
	 */
	public static function insertId($sequence=NULL)
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
		return self::getConnection()->command();
	}



	/**
	 * @param  string    column name
	 * @return DibiFluent
	 */
	public static function select($args)
	{
		$args = func_get_args();
		return call_user_func_array(array(self::getConnection(), 'select'), $args);
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



	/********************* data types ****************d*g**/



	/**
	 * @deprecated
	 */
	public static function datetime($time = NULL)
	{
		return new DateTime53(is_numeric($time) ? date('Y-m-d H:i:s', $time) : $time);
	}



	/**
	 * @deprecated
	 */
	public static function date($date = NULL)
	{
		return new DateTime53(is_numeric($date) ? date('Y-m-d', $date) : $date);
	}



	/********************* substitutions ****************d*g**/



	/**
	 * Create a new substitution pair for indentifiers.
	 * @param  string from
	 * @param  string to
	 * @return void
	 */
	public static function addSubst($expr, $subst)
	{
		self::$substs[$expr] = $subst;
	}



	/**
	 * Remove substitution pair.
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
	 * Sets substitution fallback handler.
	 * @param  callback
	 * @return void
	 */
	public static function setSubstFallback($callback)
	{
		if (!is_callable($callback)) {
			$able = is_callable($callback, TRUE, $textual);
			throw new InvalidArgumentException("Handler '$textual' is not " . ($able ? 'callable.' : 'valid PHP callback.'));
		}

		self::$substFallBack = $callback;
	}



	/**
	 * Default substitution fallback handler.
	 * @param  string
	 * @return mixed
	 */
	public static function defaultSubstFallback($expr)
	{
		throw new InvalidStateException("Missing substitution for '$expr' expression.");
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
		ob_start();
		if ($sql instanceof DibiResult) {
			$sql->dump();

		} else {
			if ($sql === NULL) $sql = self::$sql;

			static $keywords1 = 'SELECT|UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';
			static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|TRUE|FALSE';

			// insert new lines
			$sql = " $sql ";
			$sql = preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

			// reduce spaces
			$sql = preg_replace('#[ \t]{2,}#', " ", $sql);

			$sql = wordwrap($sql, 100);
			$sql = htmlSpecialChars($sql);
			$sql = preg_replace("#([ \t]*\r?\n){2,}#", "\n", $sql);

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

}
