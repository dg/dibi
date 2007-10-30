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
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  (dibi license)
 * @category   Database
 * @package    Dibi
 * @link       http://php7.org/dibi/
 */



/**
 * dibi Common Driver
 *
 * @version $Revision$ $Date$
 */
abstract class DibiDriver
{
    /**
     * Current connection configuration
     * @var array
     */
    private $config;

    /**
     * Connection resource
     * @var resource
     */
    private $connection;

    /**
     * Describes how convert some datatypes to SQL command
     * @var array
     */
    public $formats = array(
        'TRUE'     => "1",             // boolean true
        'FALSE'    => "0",             // boolean false
        'date'     => "'Y-m-d'",       // format used by date()
        'datetime' => "'Y-m-d H:i:s'", // format used by date()
    );



    /**
     * Creates object and (optionally) connects to a database
     *
     * @param array  connect configuration
     * @throws DibiException
     */
    public function __construct($config)
    {
        $this->config = $config;

        if (empty($config['lazy'])) {
            $this->connection = $this->connect();
        }
    }



    /**
     * Connects to a database
     *
     * @throws DibiException
     * @return resource
     */
    abstract protected function connect();



    /**
     * Returns configuration variable. If no $key is passed, returns the entire array.
     *
     * @see DibiDriver::__construct
     * @param string
     * @param mixed  default value to use if key not found
     * @return mixed
     */
    final public function getConfig($key = NULL, $default = NULL)
    {
        if ($key === NULL) {
            return $this->config;

        } elseif (isset($this->config[$key])) {
            return $this->config[$key];

        } else {
            return $default;
        }
    }



    /**
     * Returns the connection resource
     *
     * @return resource
     */
    final public function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->connect();
        }

        return $this->connection;
    }



    /**
     * Generates (translates) and executes SQL query
     *
     * @param  array|mixed    one or more arguments
     * @return DibiResult|TRUE
     * @throws DibiException
     */
    final public function query($args)
    {
        if (!is_array($args)) $args = func_get_args();

        $trans = new DibiTranslator($this);
        if ($trans->translate($args)) {
            return $this->nativeQuery($trans->sql);
        } else {
            throw new DibiException('SQL translate error: ' . $trans->sql);
        }
    }



    /**
     * Generates and prints SQL query
     *
     * @param  array|mixed  one or more arguments
     * @return bool
     */
    final public function test($args)
    {
        if (!is_array($args)) $args = func_get_args();

        $trans = new DibiTranslator($this);
        $ok = $trans->translate($args);
        dibi::dump($trans->sql);
        return $ok;
    }



    /**
     * Executes the SQL query
     *
     * @param string        SQL statement.
     * @return DibiResult|TRUE  Result set object
     * @throws DibiException
     */
    public function nativeQuery($sql)
    {
        dibi::notify('beforeQuery', $this, $sql);
        $res = $this->doQuery($sql);
        dibi::notify('afterQuery', $this, $res);
        return $res;
    }



    /**
     * Apply configuration alias or default values
     *
     * @param array  connect configuration
     * @param string key
     * @param string alias key
     * @return void
     */
    protected static function prepare(&$config, $key, $alias=NULL)
    {
        if (isset($config[$key])) return;

        if ($alias !== NULL && isset($config[$alias])) {
            $config[$key] = $config[$alias];
            unset($config[$alias]);
        } else {
            $config[$key] = NULL;
        }
    }



    /**
     * Internal: Executes the SQL query
     *
     * @param string       SQL statement.
     * @return DibiResult|TRUE  Result set object
     * @throws DibiDatabaseException
     */
    abstract protected function doQuery($sql);



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    abstract public function affectedRows();



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    abstract public function insertId();



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    abstract public function begin();



    /**
     * Commits statements in a transaction.
     * @return void
     */
    abstract public function commit();



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    abstract public function rollback();



    /**
     * Returns last error
     *
     * @return array with items 'message' and 'code'
     */
    abstract public function errorInfo();



    /**
     * Escapes the string
     *
     * @param string     unescaped string
     * @param bool       quote string?
     * @return string    escaped and optionally quoted string
     */
    abstract public function escape($value, $appendQuotes = TRUE);



    /**
     * Delimites identifier (table's or column's name, etc.)
     *
     * @param string     identifier
     * @return string    delimited identifier
     */
    abstract public function delimite($value);



    /**
     * Gets a information of the current database.
     *
     * @return DibiMetaData
     */
    abstract public function getMetaData();



    /**
     * Experimental - injects LIMIT/OFFSET to the SQL query
     *
     * @param string &$sql  The SQL query that will be modified.
     * @param int $limit
     * @param int $offset
     * @return void
     */
    abstract public function applyLimit(&$sql, $limit, $offset = 0);



    /**#@+
     * Access to undeclared property
     * @throws Exception
     */
    private function &__get($name) { throw new Exception("Access to undeclared property: " . get_class($this) . "::$$name"); }
    private function __set($name, $value) { throw new Exception("Access to undeclared property: " . get_class($this) . "::$$name"); }
    private function __unset($name) { throw new Exception("Access to undeclared property: " . get_class($this) . "::$$name"); }
    /**#@-*/


} // class DibiDriver
