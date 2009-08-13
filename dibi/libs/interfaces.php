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
 * Interface for user variable, used for generating SQL.
 * @package dibi
 */
interface IDibiVariable
{
	/**
	 * Format for SQL.
	 * @param  DibiTranslator
	 * @param  string  optional modifier
	 * @return string  SQL code
	 */
	function toSql(DibiTranslator $translator, $modifier);
}





/**
 * Provides an interface between a dataset and data-aware components.
 * @package dibi
 */
interface IDataSource extends Countable, IteratorAggregate
{
	//function IteratorAggregate::getIterator();
	//function Countable::count();
}





/**
 * Defines method that must profiler implement.
 * @package dibi
 */
interface IDibiProfiler
{
	/**#@+ event type */
	const CONNECT = 1;
	const SELECT = 4;
	const INSERT = 8;
	const DELETE = 16;
	const UPDATE = 32;
	const QUERY = 60;
	const BEGIN = 64;
	const COMMIT = 128;
	const ROLLBACK = 256;
	const TRANSACTION = 448;
	const EXCEPTION = 512;
	const ALL = 1023;
	/**#@-*/

	/**
	 * Before event notification.
	 * @param  DibiConnection
	 * @param  int     event name
	 * @param  string  sql
	 * @return int
	 */
	function before(DibiConnection $connection, $event, $sql = NULL);

	/**
	 * After event notification.
	 * @param  int
	 * @param  DibiResult
	 * @return void
	 */
	function after($ticket, $result = NULL);

	/**
	 * After exception notification.
	 * @param  DibiDriverException
	 * @return void
	 */
	function exception(DibiDriverException $exception);

}





/**
 * dibi driver interface.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
interface IDibiDriver
{

	/**
	 * Connects to a database.
	 * @param  array
	 * @return void
	 * @throws DibiException
	 */
	function connect(array &$config);

	/**
	 * Disconnects from a database.
	 * @return void
	 * @throws DibiException
	 */
	function disconnect();

	/**
	 * Internal: Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return IDibiDriver|NULL
	 * @throws DibiDriverException
	 */
	function query($sql);

	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	function getAffectedRows();

	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	function getInsertId($sequence);

	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	function begin($savepoint = NULL);

	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	function commit($savepoint = NULL);

	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DibiDriverException
	 */
	function rollback($savepoint = NULL);

	/**
	 * Returns the connection resource.
	 * @return mixed
	 */
	function getResource();



	/********************* SQL ****************d*g**/



	/**
	 * Encodes data for use in a SQL statement.
	 * @param  string    value
	 * @param  string    type (dibi::TEXT, dibi::BOOL, ...)
	 * @return string    encoded value
	 * @throws InvalidArgumentException
	 */
	function escape($value, $type);

	/**
	 * Decodes data from result set.
	 * @param  string    value
	 * @param  string    type (dibi::BINARY)
	 * @return string    decoded value
	 * @throws InvalidArgumentException
	 */
	function unescape($value, $type);

	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 * @param  string &$sql  The SQL query that will be modified.
	 * @param  int $limit
	 * @param  int $offset
	 * @return void
	 */
	function applyLimit(&$sql, $limit, $offset);



	/********************* result set ****************d*g**/



	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	function getRowCount();

	/**
	 * Moves cursor position without fetching row.
	 * @param  int      the 0-based cursor pos to seek to
	 * @return boolean  TRUE on success, FALSE if unable to seek to specified record
	 * @throws DibiException
	 */
	function seek($row);

	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 * @internal
	 */
	function fetch($type);

	/**
	 * Frees the resources allocated for this result set.
	 * @param  resource  result set resource
	 * @return void
	 */
	function free();

	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 * @throws DibiException
	 */
	function getColumnsMeta();

	/**
	 * Returns the result set resource.
	 * @return mixed
	 */
	function getResultResource();



	/********************* reflection ****************d*g**/



	/**
	 * Returns list of tables.
	 * @return array
	 */
	function getTables();

	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	function getColumns($table);

	/**
	 * Returns metadata for all indexes in a table.
	 * @param  string
	 * @return array
	 */
	function getIndexes($table);

	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	function getForeignKeys($table);

}
