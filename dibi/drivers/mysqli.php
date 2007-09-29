<?php

/**
 * This file is part of the "dibi" project (http://dibi.texy.info/)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005-2007 David Grudl aka -dgx- (http://www.dgx.cz)
 * @license    New BSD License
 * @version    $Revision$ $Date$
 * @category   Database
 * @package    Dibi
 */


/**
 * The dibi driver for MySQLi database
 *
 */
class DibiMySqliDriver extends DibiDriver
{
    public $formats = array(
        'TRUE'     => "1",
        'FALSE'    => "0",
        'date'     => "'Y-m-d'",
        'datetime' => "'Y-m-d H:i:s'",
    );



    /**
     * @param array  connect configuration
     * @throws DibiException
     */
    public function __construct($config)
    {
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
        if (!extension_loaded('mysqli')) {
            throw new DibiException("PHP extension 'mysqli' is not loaded");
        }

        $config = $this->getConfig();

        $connection = @mysqli_connect($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);

        if (!$connection) {
            throw new DibiDatabaseException(mysqli_connect_error(), mysqli_connect_errno());
        }

        if (!empty($config['charset'])) {
            mysqli_query($connection, "SET NAMES '" . $config['charset'] . "'");
        }

        dibi::notify('connected', $this);
        return $connection;
    }



    protected function doQuery($sql)
    {
        $connection = $this->getConnection();
        $res = @mysqli_query($connection, $sql);

        if ($res === FALSE) {
            throw new DibiDatabaseException(mysqli_error($connection), mysqli_errno($connection), $sql);

        } elseif (is_object($res)) {
            return new DibiMySqliResult($res);
        }
    }



    public function affectedRows()
    {
        $rows = mysqli_affected_rows($this->getConnection());
        return $rows < 0 ? FALSE : $rows;
    }



    public function insertId()
    {
        $id = mysqli_insert_id($this->getConnection());
        return $id < 1 ? FALSE : $id;
    }



    public function begin()
    {
        $connection = $this->getConnection();
        if (!mysqli_autocommit($connection, FALSE)) {
            throw new DibiDatabaseException(mysqli_error($connection), mysqli_errno($connection));
        }
        dibi::notify('begin', $this);
    }



    public function commit()
    {
        $connection = $this->getConnection();
        if (!mysqli_commit($connection)) {
            throw new DibiDatabaseException(mysqli_error($connection), mysqli_errno($connection));
        }
        mysqli_autocommit($connection, TRUE);
        dibi::notify('commit', $this);
    }



    public function rollback()
    {
        $connection = $this->getConnection();
        if (!mysqli_rollback($connection)) {
            throw new DibiDatabaseException(mysqli_error($connection), mysqli_errno($connection));
        }
        mysqli_autocommit($connection, TRUE);
        dibi::notify('rollback', $this);
    }



    public function errorInfo()
    {
        $connection = $this->getConnection();
        return array(
            'message'  => mysqli_error($connection),
            'code'     => mysqli_errno($connection),
        );
    }



    public function escape($value, $appendQuotes = TRUE)
    {
        $connection = $this->getConnection();
        return $appendQuotes
               ? "'" . mysqli_real_escape_string($connection, $value) . "'"
               : mysqli_real_escape_string($connection, $value);
    }



    public function delimite($value)
    {
        return '`' . str_replace('.', '`.`', $value) . '`';
    }



    public function getMetaData()
    {
        throw new DibiException(__METHOD__ . ' is not implemented');
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

            if ($info['flags'] & MYSQLI_AUTO_INCREMENT_FLAG) { // or 'primary_key' ?
                $info['type'] = dibi::FIELD_COUNTER;
            } else {
                $info['type'] = isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN;
//                if ($info['type'] === dibi::FIELD_TEXT && $info['length'] > 255)
//                    $info['type'] = dibi::FIELD_LONG_TEXT;
            }

            $this->meta[$info['name']] = $info;
            $this->convert[$info['name']] = $info['type'];
        }
    }


} // class DibiMySqliResult
