<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2007 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://php7.org/dibi/
 *
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  dibi license
 * @link       http://php7.org/dibi/
 * @package    dibi
 */



/**
 * dibi driver interface
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
interface DibiDriverInterface
{

    /**
     * Internal: Connects to a database
     *
     * @param  array
     * @return void
     * @throws DibiException
     */
    function connect(array &$config);



    /**
     * Internal: Disconnects from a database
     *
     * @return void
     * @throws DibiException
     */
    function disconnect();



    /**
     * Internal: Executes the SQL query
     *
     * @param string       SQL statement.
     * @return bool        have resultset?
     * @throws DibiDatabaseException
     */
    function query($sql);



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    function affectedRows();



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    function insertId($sequence);



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    function begin();



    /**
     * Commits statements in a transaction.
     * @return void
     */
    function commit();



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    function rollback();



    /**
     * Format to SQL command
     *
     * @param string     value
     * @param string     type (dibi::FIELD_TEXT, dibi::FIELD_BOOL, dibi::FIELD_DATE, dibi::FIELD_DATETIME, dibi::IDENTIFIER)
     * @return string    formatted value
     */
    function format($value, $type);


    /**
     * Injects LIMIT/OFFSET to the SQL query
     *
     * @param string &$sql  The SQL query that will be modified.
     * @param int $limit
     * @param int $offset
     * @return void
     */
    function applyLimit(&$sql, $limit, $offset);



    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    function rowCount();



    /**
     * Moves cursor position without fetching row
     *
     * @param  int       the 0-based cursor pos to seek to
     * @return void
     * @throws DibiException
     */
    function seek($row);



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    function fetch();



    /**
     * Frees the resources allocated for this result set
     *
     * @param  resource  resultset resource
     * @return void
     */
    function free();


    /**
     * Returns the connection resource
     *
     * @return mixed
     */
    function getResource();



    /**
     * Returns the resultset resource
     *
     * @return mixed
     */
    function getResultResource();



    /**
     * Gets a information of the current database.
     *
     * @return DibiReflection
     */
    function getDibiReflection();

}





/**
 * Interface for user variable, used for generating SQL
 * @package dibi
 */
interface DibiVariableInterface
{
    /**
     * Format for SQL
     *
     * @param  object  destination DibiDriverInterface
     * @param  string  optional modifier
     * @return string  SQL code
     */
    public function toSql(DibiDriverInterface $driver, $modifier);
}
