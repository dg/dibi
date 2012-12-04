<?php

/**
 * dibi - smart database abstraction layer (http://dibiphp.com)
 *
 * Copyright (c) 2005, 2012 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */


/**
 * Check PHP configuration.
 */
if (version_compare(PHP_VERSION, '5.2.0', '<')) {
	throw new Exception('dibi needs PHP 5.2.0 or newer.');
}

@set_magic_quotes_runtime(FALSE); // intentionally @



require_once dirname(__FILE__) . '/libs/interfaces.php';
require_once dirname(__FILE__) . '/libs/DibiDateTime.php';
require_once dirname(__FILE__) . '/libs/DibiObject.php';
require_once dirname(__FILE__) . '/libs/DibiLiteral.php';
require_once dirname(__FILE__) . '/libs/DibiHashMap.php';
require_once dirname(__FILE__) . '/libs/DibiException.php';
require_once dirname(__FILE__) . '/libs/DibiConnection.php';
require_once dirname(__FILE__) . '/libs/DibiResult.php';
require_once dirname(__FILE__) . '/libs/DibiResultIterator.php';
require_once dirname(__FILE__) . '/libs/DibiRow.php';
require_once dirname(__FILE__) . '/libs/DibiTranslator.php';
require_once dirname(__FILE__) . '/libs/DibiDataSource.php';
require_once dirname(__FILE__) . '/libs/DibiFluent.php';
require_once dirname(__FILE__) . '/libs/DibiDatabaseInfo.php';
require_once dirname(__FILE__) . '/libs/DibiEvent.php';
require_once dirname(__FILE__) . '/libs/DibiFileLogger.php';
require_once dirname(__FILE__) . '/libs/DibiFirePhpLogger.php';
if (interface_exists('Nette\Diagnostics\IBarPanel') || interface_exists('IBarPanel')) {
	require_once dirname(__FILE__) . '/Nette/DibiNettePanel.php';
}





/**
 * Interface for database drivers.
 *
 * This class is static container class for creating DB objects and
 * store connections info.
 *
 * @author     David Grudl
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

	const IDENTIFIER = 'n';

	/** @deprecated */
	const FIELD_TEXT = dibi::TEXT,
		FIELD_BINARY = dibi::BINARY,
		FIELD_BOOL = dibi::BOOL,
		FIELD_INTEGER = dibi::INTEGER,
		FIELD_FLOAT = dibi::FLOAT,
		FIELD_DATE = dibi::DATE,
		FIELD_DATETIME = dibi::DATETIME,
		FIELD_TIME = dibi::TIME;

	/** version */
	const VERSION = '2.0.1',
		REVISION = '$WCREV$ released on $WCDATE$';

	/** sorting order */
	const ASC = 'ASC',
		DESC = 'DESC';

	/** @var DibiConnection[]  Connection registry storage for DibiConnection objects */
	private static $registry = array();

	/** @var DibiConnection  Current connection */
	private static $connection;

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
	 * Sets connection.
	 * @param  DibiConnection
	 * @return DibiConnection
	 */
	public static function setConnection(DibiConnection $connection)
	{
		return self::$connection = $connection;
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
	 * @return DibiDateTime
	 */
	public static function datetime($time = NULL)
	{
		trigger_error(__METHOD__ . '() is deprecated; create DibiDateTime object instead.', E_USER_WARNING);
		return new DibiDateTime($time);
	}



	/**
	 * @deprecated
	 */
	public static function date($date = NULL)
	{
		trigger_error(__METHOD__ . '() is deprecated; create DibiDateTime object instead.', E_USER_WARNING);
		return new DibiDateTime($date);
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



	/** @deprecated */
	public static function addSubst($expr, $subst)
	{
		trigger_error(__METHOD__ . '() is deprecated; use dibi::getSubstitutes()->expr = val; instead.', E_USER_WARNING);
		self::getSubstitutes()->$expr = $subst;
	}



	/** @deprecated */
	public static function removeSubst($expr)
	{
		trigger_error(__METHOD__ . '() is deprecated; use unset(dibi::getSubstitutes()->expr) instead.', E_USER_WARNING);
		$substitutes = self::getSubstitutes();
		if ($expr === TRUE) {
			foreach ($substitutes as $expr => $foo) {
				unset($substitutes->$expr);
			}
		} else {
			unset($substitutes->$expr);
		}
	}



	/** @deprecated */
	public static function setSubstFallback($callback)
	{
		trigger_error(__METHOD__ . '() is deprecated; use dibi::getSubstitutes()->setCallback() instead.', E_USER_WARNING);
		self::getSubstitutes()->setCallback($callback);
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

			static $keywords1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';
			static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|RLIKE|REGEXP|TRUE|FALSE';

			// insert new lines
			$sql = " $sql ";
			$sql = preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

			// reduce spaces
			$sql = preg_replace('#[ \t]{2,}#', " ", $sql);

			$sql = wordwrap($sql, 100);
			$sql = preg_replace("#([ \t]*\r?\n){2,}#", "\n", $sql);

			// syntax highlight
			$highlighter = "#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is";
			if (PHP_SAPI === 'cli') {
				if (substr(getenv('TERM'), 0, 5) === 'xterm') {
					$sql = preg_replace_callback($highlighter, array('dibi', 'cliHighlightCallback'), $sql);
				}
				echo trim($sql) . "\n\n";

			} else {
				$sql = htmlSpecialChars($sql);
				$sql = preg_replace_callback($highlighter, array('dibi', 'highlightCallback'), $sql);
				echo '<pre class="dump">', trim($sql), "</pre>\n";
			}
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



	private static function cliHighlightCallback($matches)
	{
		if (!empty($matches[1])) // comment
			return "\033[1;30m" . $matches[1] . "\033[0m";

		if (!empty($matches[2])) // error
			return "\033[1;31m" . $matches[2] . "\033[0m";

		if (!empty($matches[3])) // most important keywords
			return "\033[1;34m" . $matches[3] . "\033[0m";

		if (!empty($matches[4])) // other keywords
			return "\033[1;32m" . $matches[4] . "\033[0m";
	}

}
