<?php

/**
 * This file is part of the "dibi" project (http://dibi.texy.info/)
 *
 * Copyright (c) 2005-2007 David Grudl aka -dgx- <dave@dgx.cz>
 *
 * @version  $Revision$ $Date$
 * @package  dibi
 */


// security - include dibi.php, not this file
if (!defined('DIBI')) die();


/**
 * The dibi driver interacting with databases via ODBC connections
 *
 */
class DibiOdbcDriver extends DibiDriver
{
    private
        $affectedRows = FALSE;

    public
        $formats = array(
            'TRUE'     => "-1",
            'FALSE'    => "0",
            'date'     => "#m/d/Y#",
            'datetime' => "#m/d/Y H:i:s#",
        );



    /**
     * @param array  connect configuration
     * @throw  DibiException
     */
    public function __construct($config)
    {
        if (!extension_loaded('odbc'))
            throw new DibiException("PHP extension 'odbc' is not loaded");

        // default values
        if (empty($config['username'])) $config['username'] = ini_get('odbc.default_user');
        if (empty($config['password'])) $config['password'] = ini_get('odbc.default_pw');
        if (empty($config['database'])) $config['database'] = ini_get('odbc.default_db');

        if (empty($config['username']))
            throw new DibiException("Username must be specified");

        if (empty($config['password']))
            throw new DibiException("Password must be specified");

        if (empty($config['database']))
            throw new DibiException("Database must be specified");

        parent::__construct($config);
    }



    protected function connect()
    {
        $config = $this->config;

        if (empty($config['persistent']))
            $conn = @odbc_connect($config['database'], $config['username'], $config['password']);
        else
            $conn = @odbc_pconnect($config['database'], $config['username'], $config['password']);

        if (!is_resource($conn))
            throw new DibiException("Connecting error", array(
                'message' => odbc_errormsg(),
                'code'    => odbc_error(),
            ));

        return $conn;
    }



    public function nativeQuery($sql)
    {
        $this->affectedRows = FALSE;

        $conn = $this->getResource();
        $res = @odbc_exec($conn, $sql);

        if ($res === FALSE) return FALSE;

        $this->affectedRows = odbc_num_rows($conn);
        if ($this->affectedRows < 0) $this->affectedRows = FALSE;

        if (is_resource($res))
            return new DibiOdbcResult($res);

        return TRUE;
    }


    public function affectedRows()
    {
        return $this->affectedRows;
    }


    public function insertId()
    {
        return FALSE;
    }


    public function begin()
    {
        return odbc_autocommit($this->getResource(), FALSE);
    }


    public function commit()
    {
        $conn = $this->getResource();
        $ok = odbc_commit($conn);
        odbc_autocommit($conn, TRUE);
        return $ok;
    }


    public function rollback()
    {
        $conn = $this->getResource();
        $ok = odbc_rollback($conn);
        odbc_autocommit($conn, TRUE);
        return $ok;
    }


    public function errorInfo()
    {
        $conn = $this->getResource();
        return array(
            'message'  => odbc_errormsg($conn),
            'code'     => odbc_error($conn),
        );
    }



    public function escape($value, $appendQuotes=TRUE)
    {
        $value = str_replace("'", "''", $value);
        return $appendQuotes
               ? "'" . $value . "'"
               : $value;
    }


    public function delimite($value)
    {
        return '[' . str_replace('.', '].[', $value) . ']';
    }


    public function getMetaData()
    {
        trigger_error('Meta is not implemented yet.', E_USER_WARNING);
    }


    /**
     * @see DibiDriver::applyLimit()
     */
    public function applyLimit(&$sql, $limit, $offset = 0)
    {
        // offset suppot is missing...
        if ($limit >= 0)
           $sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ')';

        if ($offset) throw new DibiException('Offset is not implemented.');
    }


} // class DibiOdbcDriver







class DibiOdbcResult extends DibiResult
{
    private $resource;
    private $row = 0;


    public function __construct($resource)
    {
        $this->resource = $resource;
    }


    public function rowCount()
    {
        // will return -1 with many drivers :-(
        return odbc_num_rows($this->resource);
    }


    protected function doFetch()
    {
        return odbc_fetch_array($this->resource, $this->row++);
    }


    public function seek($row)
    {
        $this->row = $row;
    }


    protected function free()
    {
        odbc_free_result($this->resource);
    }


    /** this is experimental */
    protected function buildMeta()
    {
        // cache
        if ($this->meta !== NULL)
            return $this->meta;

        static $types = array(
            'CHAR'      => dibi::FIELD_TEXT,
            'COUNTER'   => dibi::FIELD_COUNTER,
            'VARCHAR'   => dibi::FIELD_TEXT,
            'LONGCHAR'  => dibi::FIELD_TEXT,
            'INTEGER'   => dibi::FIELD_INTEGER,
            'DATETIME'  => dibi::FIELD_DATETIME,
            'CURRENCY'  => dibi::FIELD_FLOAT,
            'BIT'       => dibi::FIELD_BOOL,
            'LONGBINARY'=> dibi::FIELD_BINARY,
            'SMALLINT'  => dibi::FIELD_INTEGER,
            'BYTE'      => dibi::FIELD_INTEGER,
            'BIGINT'    => dibi::FIELD_INTEGER,
            'INT'       => dibi::FIELD_INTEGER,
            'TINYINT'   => dibi::FIELD_INTEGER,
            'REAL'      => dibi::FIELD_FLOAT,
            'DOUBLE'    => dibi::FIELD_FLOAT,
            'DECIMAL'   => dibi::FIELD_FLOAT,
            'NUMERIC'   => dibi::FIELD_FLOAT,
            'MONEY'     => dibi::FIELD_FLOAT,
            'SMALLMONEY'=> dibi::FIELD_FLOAT,
            'FLOAT'     => dibi::FIELD_FLOAT,
            'YESNO'     => dibi::FIELD_BOOL,
            // and many others?
        );

        $count = odbc_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 1; $index <= $count; $index++) {
            $native = strtoupper(odbc_field_type($this->resource, $index));
            $name = odbc_field_name($this->resource, $index);
            $this->meta[$name] = array(
                'type'      => isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN,
                'native'    => $native,
                'length'    => odbc_field_len($this->resource, $index),
                'scale'     => odbc_field_scale($this->resource, $index),
                'precision' => odbc_field_precision($this->resource, $index),
            );
            $this->convert[$name] = $this->meta[$name]['type'];
        }
    }


} // class DibiOdbcResult
