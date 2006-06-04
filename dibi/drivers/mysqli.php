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
 * @license    GNU GENERAL PUBLIC LICENSE
 * @package    dibi
 * @category   Database
 * @version    0.5alpha (2006-05-26) for PHP5
 */


// security - include dibi.php, not this file
if (!defined('dibi')) die();


/**
 * The dibi driver for MySQLi database
 *
 */
class DibiMySqliDriver extends DibiDriver {
    private
        $conn;

    public
        $formats = array(
            'NULL'     => "NULL",
            'TRUE'     => "1",
            'FALSE'    => "0",
            'date'     => "'Y-m-d'",
            'datetime' => "'Y-m-d H:i:s'",
        );



    public static function connect($config)
    {
        if (!extension_loaded('mysqli'))
            return new DibiException("PHP extension 'mysqli' is not loaded");

        if (empty($config['host'])) $config['host'] = 'localhost';

        $conn = @mysqli_connect($config['host'], @$config['username'], @$config['password'], @$config['database'], @$config['port']);

        if (!$conn)
            return new DibiException("Connecting error", array(
                'message' => mysqli_connect_error(),
                'code'    => mysqli_connect_errno(),
            ));

        if (!empty($config['charset']))
            mysqli_query($conn, 'SET CHARACTER SET '.$config['charset']);

        $obj = new self($config);
        $obj->conn = $conn;
        return $obj;
    }



    public function query($sql)
    {
        $res = @mysqli_query($this->conn, $sql);

        if (is_object($res))
            return new DibiMySqliResult($res);

        if ($res === FALSE)
            return new DibiException("Query error", $this->errorInfo($sql));

        return TRUE;
    }


    public function affectedRows()
    {
        $rows = mysqli_affected_rows($this->conn);
        return $rows < 0 ? FALSE : $rows;
    }


    public function insertId()
    {
        $id = mysqli_insert_id($this->conn);
        return $id < 1 ? FALSE : $id;
    }


    public function begin()
    {
        return mysqli_autocommit($this->conn, FALSE);
    }


    public function commit()
    {
        $ok = mysqli_commit($this->conn);
        mysqli_autocommit($this->conn, TRUE);
        return $ok;
    }


    public function rollback()
    {
        $ok = mysqli_rollback($this->conn);
        mysqli_autocommit($this->conn, TRUE);
        return $ok;
    }


    private function errorInfo($sql = NULL)
    {
        return array(
            'message'  => mysqli_error($this->conn),
            'code'     => mysqli_errno($this->conn),
            'sql'      => $sql,
        );
    }




    public function escape($value, $appendQuotes = FALSE)
    {
        return $appendQuotes
               ? "'" . mysqli_real_escape_string($this->conn, $value) . "'"
               : mysqli_real_escape_string($this->conn, $value);
    }


    public function quoteName($value)
    {
        return '`' . strtr($value, array('.' => '`.`')) . '`';
    }



    public function getMetaData()
    {
        trigger_error('Meta is not implemented yet.', E_USER_WARNING);
    }


} // class DibiMySqliDriver









class DibiMySqliResult extends DibiResult
{
    private
        $resource,
        $meta;


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


    public function getFields()
    {
        // cache
        if ($this->meta === NULL)
            $this->createMeta();

        return array_keys($this->meta);
    }


    protected function detectTypes()
    {
        if ($this->meta === NULL)
            $this->createMeta();
    }

    /** this is experimental */
    public function getMetaData($field)
    {
        // cache
        if ($this->meta === NULL)
            $this->createMeta();

        return isset($this->meta[$field]) ? $this->meta[$field] : FALSE;
    }



    /** this is experimental */
    private function createMeta()
    {
        static $types = array(
            MYSQLI_TYPE_FLOAT     => self::FIELD_FLOAT,
            MYSQLI_TYPE_DOUBLE    => self::FIELD_FLOAT,
            MYSQLI_TYPE_DECIMAL   => self::FIELD_FLOAT,
    //      MYSQLI_TYPE_NEWDECIMAL=> self::FIELD_FLOAT,
    //      MYSQLI_TYPE_BIT       => self::FIELD_INTEGER,
            MYSQLI_TYPE_TINY      => self::FIELD_INTEGER,
            MYSQLI_TYPE_SHORT     => self::FIELD_INTEGER,
            MYSQLI_TYPE_LONG      => self::FIELD_INTEGER,
            MYSQLI_TYPE_LONGLONG  => self::FIELD_INTEGER,
            MYSQLI_TYPE_INT24     => self::FIELD_INTEGER,
            MYSQLI_TYPE_YEAR      => self::FIELD_INTEGER,
            MYSQLI_TYPE_GEOMETRY  => self::FIELD_INTEGER,
            MYSQLI_TYPE_DATE      => self::FIELD_DATE,
            MYSQLI_TYPE_NEWDATE   => self::FIELD_DATE,
            MYSQLI_TYPE_TIMESTAMP => self::FIELD_DATETIME,
            MYSQLI_TYPE_TIME      => self::FIELD_DATETIME,
            MYSQLI_TYPE_DATETIME  => self::FIELD_DATETIME,
            MYSQLI_TYPE_ENUM      => self::FIELD_TEXT,   // eventually self::FIELD_INTEGER
            MYSQLI_TYPE_SET       => self::FIELD_TEXT,    // eventually self::FIELD_INTEGER
            MYSQLI_TYPE_STRING    => self::FIELD_TEXT,
            MYSQLI_TYPE_VAR_STRING=> self::FIELD_TEXT,
            MYSQLI_TYPE_TINY_BLOB => self::FIELD_BINARY,
            MYSQLI_TYPE_MEDIUM_BLOB=> self::FIELD_BINARY,
            MYSQLI_TYPE_LONG_BLOB => self::FIELD_BINARY,
            MYSQLI_TYPE_BLOB      => self::FIELD_BINARY,
        );

        $count = mysqli_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {
            $info = (array) mysqli_fetch_field_direct($this->resource, $index);
            $native = $info['native'] = $info['type'];

            if ($info['flags'] & MYSQLI_AUTO_INCREMENT_FLAG)  // or 'primary_key' ?
                $info['type'] = self::FIELD_COUNTER;
            else {
                $info['type'] = isset($types[$native]) ? $types[$native] : self::FIELD_UNKNOWN;
//                if ($info['type'] == self::FIELD_TEXT && $info['length'] > 255)
//                    $info['type'] = self::FIELD_LONG_TEXT;
            }

            $this->meta[$info['name']] = $info;
            $this->convert[$info['name']] = $info['type'];
        }
    }


} // class DibiMySqliResult





?>