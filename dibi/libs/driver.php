<?php

/**
 * dibi - Database Abstraction Layer according to dgx
 * --------------------------------------------------
 *
 * This source file is subject to the GNU GPL license.
 *
 * @author     David Grudl aka -dgx- <dave@dgx.cz>
 * @link       http://texy.info/dibi/
 * @copyright  Copyright (c) 2005-2006 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE v2
 * @package    dibi
 * @category   Database
 * @version    $Revision$ $Date$
 */


// security - include dibi.php, not this file
if (!defined('DIBI')) die();



/**
 * dibi Common Driver
 *
 */
abstract class DibiDriver
{
    /**
     * Current connection configuration
     * @var array
     */
    protected
        $config;

    /**
     * Describes how convert some datatypes to SQL command
     * @var array
     */
    public $formats = array(
        'NULL'     => "NULL",          // NULL
        'TRUE'     => "1",             // boolean true
        'FALSE'    => "0",             // boolean false
        'date'     => "'Y-m-d'",       // format used by date()
        'datetime' => "'Y-m-d H:i:s'", // format used by date()
    );


    /**
     * DibiDriver factory: creates object and connects to a database
     *
     * @param array         connect configuration
     * @return bool|object  DibiDriver object on success, FALSE or Exception on failure
     */
    abstract static public function connect($config);



    /**
     * Protected constructor. Must be initialized using the factory method.
     * @see DibiDriver::connect()
     * @param array  connect configuration
     */
    protected function __construct($config)
    {
        $this->config = $config;
    }


    /**
     * Get the configuration descriptor used by connect() to connect to database.
     * @see DibiDriver::connect()
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }



    /**
     * Executes the SQL query
     *
     * @param string        SQL statement.
     * @return object|bool  Result set object or TRUE on success, Exception on failure
     */
    abstract public function query($sql);


    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    abstract public function affectedRows();


    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     * @return int|bool  int on success or FALSE on failure
     */
    abstract public function insertId();


    /**
     * Begins a transaction (if supported).
     */
    abstract public function begin();


    /**
     * Commits statements in a transaction.
     */
    abstract public function commit();


    /**
     * Rollback changes in a transaction.
     */
    abstract public function rollback();




    /**
     * Escapes the string
     * @param string     unescaped string
     * @param bool       quote string?
     * @return string    escaped and optionally quoted string
     */
    abstract public function escape($value, $appendQuotes = FALSE);


    /**
     * Quotes SQL identifier (table's or column's name, etc.)
     * @param string     identifier
     * @return string    quoted identifier
     */
    abstract public function quoteName($value);



    /**
     * Gets a information of the current database.
     *
     * @return DibiMetaData
     */
    abstract public function getMetaData();


    /**
     * Experimental - injects LIMIT/OFFSET to the SQL query
     * @param string &$sql  The SQL query that will be modified.
     * @param int $limit
     * @param int $offset
     * @return void
     */
    abstract public function applyLimit(&$sql, $limit, $offset = 0);


    /**
     * Undefined property usage prevention
     */
    function __get($nm) { throw new Exception("Undefined property '" . get_class($this) . "::$$nm'"); }
    function __set($nm, $val) { $this->__get($nm); }
    private function __unset($nm) { $this->__get($nm); }
    private function __isset($nm) { $this->__get($nm); }


} // class DibiDriver
