<?php

/**
 * This file is part of the "dibi" project (http://dibi.texy.info/)
 *
 * Copyright (c) 2005-2007 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * @version  $Revision$ $Date$
 * @package  dibi
 */


// security - include dibi.php, not this file
if (!class_exists('dibi', FALSE)) die();



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
    protected $config;

    /**
     * Connection resource
     * @var resource
     */
    private $res;

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
     * @param array  connect configuration
     * @throw  DibiException
     */
    public function __construct($config)
    {
        $this->config = $config;
        if (empty($config['lazy'])) $this->res = $this->connect();
    }



    /**
     * Connects to a database
     * @throw  DibiException
     * @return resource
     */
    abstract protected function connect();



    /**
     * Gets the configuration descriptor
     * @see DibiDriver::__construct
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }



    /**
     * Returns the connection resource
     * @return resource
     */
    public function getResource()
    {
        if (!$this->res) $this->res = $this->connect();

        return $this->res;
    }



    /**
     * Generates and executes SQL query
     *
     * @param  array|mixed    one or more arguments
     * @return int|DibiResult
     * @throw  DibiException
     */
    public function query($args)
    {
        // receive arguments
        if (!is_array($args))
            $args = func_get_args();

        // and generate SQL
        $trans = new DibiTranslator($this);
        dibi::$sql = $sql = $trans->translate($args);

        if ($sql === FALSE) return FALSE;

        // execute SQL
        $timer = -microtime(true);
        $res = $this->nativeQuery($sql);

        if ($res === FALSE) { // query error
            if (dibi::$logFile) { // log to file
                $info = $this->errorInfo();
                if ($info['code']) $info['message'] = "[$info[code]] $info[message]";
                dibi::log(
                    "ERROR: $info[message]"
                    . "\n-- SQL: " . $sql
                    . "\n-- driver: " . $this->config['driver']
                    . ";\n-- " . date('Y-m-d H:i:s ')
                );
            }

            if (dibi::$throwExceptions) {
                $info = $this->errorInfo();
                throw new DibiException('Query error (driver ' . $this->config['driver'] . ')', $info, $sql);
            } else {
                $info = $this->errorInfo();
                if ($info['code']) $info['message'] = "[$info[code]] $info[message]";
                trigger_error("dibi: $info[message]", E_USER_WARNING);
                return FALSE;
            }
        }

        if (dibi::$logFile && dibi::$logAll) { // log success
            $timer += microtime(true);
            $msg = $res instanceof DibiResult ? 'object('.get_class($res).') rows: '.$res->rowCount() : 'OK';

            dibi::log(
                "OK: " . $sql
                . ";\n-- result: $msg"
                . "\n-- takes: " . sprintf('%0.3f', $timer * 1000) . ' ms'
                . "\n-- driver: " . $this->config['driver']
                . "\n-- " . date('Y-m-d H:i:s ')
            );
        }

        return $res;
    }



    /**
     * Executes the SQL query
     * @param string        SQL statement.
     * @return object|bool  Result set object or TRUE on success, FALSE on failure
     */
    abstract public function nativeQuery($sql);



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
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
     * Returns last error
     * @return array with items 'message' and 'code'
     */
    abstract public function errorInfo();



    /**
     * Escapes the string
     * @param string     unescaped string
     * @param bool       quote string?
     * @return string    escaped and optionally quoted string
     */
    abstract public function escape($value, $appendQuotes=TRUE);



    /**
     * Delimites identifier (table's or column's name, etc.)
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
