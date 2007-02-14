<?php

/**
 * dibi - Database Abstraction Layer according to dgx
 * --------------------------------------------------
 *
 * This source file is subject to the GNU GPL license.
 *
 * @author     David Grudl aka -dgx- <dave@dgx.cz>
 * @link       http://dibi.texy.info/
 * @copyright  Copyright (c) 2005-2007 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE v2
 * @package    dibi
 * @category   Database
 * @version    $Revision$ $Date$
 */


// security - include dibi.php, not this file
if (!defined('DIBI')) die();


/**
 * The dibi driver for MySQLi database
 *
 */
class DibiMySqliDriver extends DibiDriver {
    private
        $conn,
        $insertId = FALSE,
        $affectedRows = FALSE;

    public
        $formats = array(
            'TRUE'     => "1",
            'FALSE'    => "0",
            'date'     => "'Y-m-d'",
            'datetime' => "'Y-m-d H:i:s'",
        );



    public static function connect($config)
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

        $conn = @mysqli_connect($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);

        if (!$conn)
            throw new DibiException("Connecting error", array(
                'message' => mysqli_connect_error(),
                'code'    => mysqli_connect_errno(),
            ));

        if (!empty($config['charset']))
            mysqli_query($conn, "SET NAMES '" . $config['charset'] . "'");

        $obj = new self($config);
        $obj->conn = $conn;
        return $obj;
    }



    public function query($sql)
    {
        $this->insertId = $this->affectedRows = FALSE;
        $res = @mysqli_query($this->conn, $sql);

        if ($res === FALSE) return FALSE;

        if (is_object($res))
            return new DibiMySqliResult($res);

        $this->affectedRows = mysqli_affected_rows($this->conn);
        if ($this->affectedRows < 0) $this->affectedRows = FALSE;

        $this->insertId = mysqli_insert_id($this->conn);
        if ($this->insertId < 1) $this->insertId = FALSE;

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


    public function errorInfo()
    {
        return array(
            'message'  => mysqli_error($this->conn),
            'code'     => mysqli_errno($this->conn),
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
