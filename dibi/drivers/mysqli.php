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
if (!class_exists('dibi', FALSE)) die();


/**
 * The dibi driver for MySQLi database
 *
 */
class DibiMySqliDriver extends DibiDriver
{
    private
        $insertId = FALSE,
        $affectedRows = FALSE;

    public
        $formats = array(
            'TRUE'     => "1",
            'FALSE'    => "0",
            'date'     => "'Y-m-d'",
            'datetime' => "'Y-m-d H:i:s'",
        );



    /**
     * @param array  connect configuration
     * @throw  DibiException
     */
    public function __construct($config)
    {
        if (!extension_loaded('mysqli'))
            throw new DibiException("PHP extension 'mysqli' is not loaded");

        // default values
        if (empty($config['username'])) $config['username'] = ini_get('mysqli.default_user');
        if (empty($config['password'])) $config['password'] = ini_get('mysqli.default_password');
        if (empty($config['host'])) {
            $config['host'] = ini_get('mysqli.default_host');
            if (empty($config['port'])) ini_get('mysqli.default_port');
            if (empty($config['host'])) $config['host'] = 'localhost';
        }
        if (!isset($config['database'])) $config['database'] = NULL;

        parent::__construct($config);
    }



    protected function connect()
    {
        $config = $this->config;

        $conn = @mysqli_connect($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);

        if (!$conn)
            throw new DibiException("Connecting error (driver mysqli)", array(
                'message' => mysqli_connect_error(),
                'code'    => mysqli_connect_errno(),
            ));

        if (!empty($config['charset']))
            mysqli_query($conn, "SET NAMES '" . $config['charset'] . "'");

        return $conn;
    }



    public function nativeQuery($sql)
    {
        $this->insertId = $this->affectedRows = FALSE;
        $conn = $this->getResource();
        $res = @mysqli_query($conn, $sql);

        if ($res === FALSE) return FALSE;

        $this->affectedRows = mysqli_affected_rows($conn);
        if ($this->affectedRows < 0) $this->affectedRows = FALSE;

        $this->insertId = mysqli_insert_id($conn);
        if ($this->insertId < 1) $this->insertId = FALSE;

        if (is_object($res))
            return new DibiMySqliResult($res);

        return TRUE;
    }


    public function affectedRows()
    {
        return $this->affectedRows;
    }


    public function insertId()
    {
        return $this->insertId;
    }


    public function begin()
    {
        return mysqli_autocommit($this->getResource(), FALSE);
    }


    public function commit()
    {
        $conn = $this->getResource();
        $ok = mysqli_commit($conn);
        mysqli_autocommit($conn, TRUE);
        return $ok;
    }


    public function rollback()
    {
        $conn = $this->getResource();
        $ok = mysqli_rollback($conn);
        mysqli_autocommit($conn, TRUE);
        return $ok;
    }


    public function errorInfo()
    {
        $conn = $this->getResource();
        return array(
            'message'  => mysqli_error($conn),
            'code'     => mysqli_errno($conn),
        );
    }




    public function escape($value, $appendQuotes=TRUE)
    {
        $conn = $this->getResource();
        return $appendQuotes
               ? "'" . mysqli_real_escape_string($conn, $value) . "'"
               : mysqli_real_escape_string($conn, $value);
    }


    public function delimite($value)
    {
        return '`' . str_replace('.', '`.`', $value) . '`';
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
        if ($limit < 0 && $offset < 1) return;

        // see http://dev.mysql.com/doc/refman/5.0/en/select.html
        $sql .= ' LIMIT ' . ($limit < 0 ? '18446744073709551615' : (int) $limit)
             . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
    }


} // class DibiMySqliDriver









class DibiMySqliResult extends DibiResult
{
    private $resource;


    public function __construct($resource)
    {
        $this->resource = $resource;
    }


    public function rowCount()
    {
        return mysqli_num_rows($this->resource);
    }


    protected function doFetch()
    {
        return mysqli_fetch_assoc($this->resource);
    }


    public function seek($row)
    {
        return mysqli_data_seek($this->resource, $row);
    }


    protected function free()
    {
        mysqli_free_result($this->resource);
    }


    /** this is experimental */
    protected function buildMeta()
    {
        static $types = array(
            MYSQLI_TYPE_FLOAT     => dibi::FIELD_FLOAT,
            MYSQLI_TYPE_DOUBLE    => dibi::FIELD_FLOAT,
            MYSQLI_TYPE_DECIMAL   => dibi::FIELD_FLOAT,
    //      MYSQLI_TYPE_NEWDECIMAL=> dibi::FIELD_FLOAT,
    //      MYSQLI_TYPE_BIT       => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_TINY      => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_SHORT     => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_LONG      => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_LONGLONG  => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_INT24     => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_YEAR      => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_GEOMETRY  => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_DATE      => dibi::FIELD_DATE,
            MYSQLI_TYPE_NEWDATE   => dibi::FIELD_DATE,
            MYSQLI_TYPE_TIMESTAMP => dibi::FIELD_DATETIME,
            MYSQLI_TYPE_TIME      => dibi::FIELD_DATETIME,
            MYSQLI_TYPE_DATETIME  => dibi::FIELD_DATETIME,
            MYSQLI_TYPE_ENUM      => dibi::FIELD_TEXT,   // eventually dibi::FIELD_INTEGER
            MYSQLI_TYPE_SET       => dibi::FIELD_TEXT,    // eventually dibi::FIELD_INTEGER
            MYSQLI_TYPE_STRING    => dibi::FIELD_TEXT,
            MYSQLI_TYPE_VAR_STRING=> dibi::FIELD_TEXT,
            MYSQLI_TYPE_TINY_BLOB => dibi::FIELD_BINARY,
            MYSQLI_TYPE_MEDIUM_BLOB=> dibi::FIELD_BINARY,
            MYSQLI_TYPE_LONG_BLOB => dibi::FIELD_BINARY,
            MYSQLI_TYPE_BLOB      => dibi::FIELD_BINARY,
        );

        $count = mysqli_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {
            $info = (array) mysqli_fetch_field_direct($this->resource, $index);
            $native = $info['native'] = $info['type'];

            if ($info['flags'] & MYSQLI_AUTO_INCREMENT_FLAG)  // or 'primary_key' ?
                $info['type'] = dibi::FIELD_COUNTER;
            else {
                $info['type'] = isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN;
//                if ($info['type'] == dibi::FIELD_TEXT && $info['length'] > 255)
//                    $info['type'] = dibi::FIELD_LONG_TEXT;
            }

            $this->meta[$info['name']] = $info;
            $this->convert[$info['name']] = $info['type'];
        }
    }


} // class DibiMySqliResult
