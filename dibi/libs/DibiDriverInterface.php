<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com/
 *
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com/
 * @package    dibi
 */



/**
 * dibi driver interface
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
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
     * @param  string      SQL statement.
     * @return bool        have resultset?
     * @throws DibiDriverException
     */
    function query($sql);



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int|FALSE  number of rows or FALSE on error
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
     * @throws DibiDriverException
     */
    function begin();



    /**
     * Commits statements in a transaction.
     * @return void
     * @throws DibiDriverException
     */
    function commit();



    /**
     * Rollback changes in a transaction.
     * @return void
     * @throws DibiDriverException
     */
    function rollback();



    /**
     * Format to SQL command
     *
     * @param  string    value
     * @param  string    type (dibi::FIELD_TEXT, dibi::FIELD_BOOL, dibi::FIELD_DATE, dibi::FIELD_DATETIME, dibi::IDENTIFIER)
     * @return string    formatted value
     */
    function format($value, $type);


    /**
     * Injects LIMIT/OFFSET to the SQL query
     *
     * @param  string &$sql  The SQL query that will be modified.
     * @param  int $limit
     * @param  int $offset
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
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     * @throws DibiException
     */
    function seek($row);



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @param  bool     TRUE for associative array, FALSE for numeric
     * @return array    array on success, nonarray if no next record
     */
    function fetch($type);



    /**
     * Frees the resources allocated for this result set
     *
     * @param  resource  resultset resource
     * @return void
     */
    function free();



    /**
     * Returns metadata for all columns in a result set
     *
     * @return array
     * @throws DibiException
     */
    function getColumnsMeta();



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
