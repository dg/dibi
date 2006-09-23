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
 * The dibi driver interacting with databases via ODBC connections
 *
 */
class DibiOdbcDriver extends DibiDriver {
    private
        $conn,
        $affectedRows = FALSE;

    public
        $formats = array(
            'NULL'     => "NULL",
            'TRUE'     => "-1",
            'FALSE'    => "0",
            'date'     => "#m/d/Y#",
            'datetime' => "#m/d/Y H:i:s#",
        );



    public static function connect($config)
    {
        if (!extension_loaded('odbc'))
            return new DibiException("PHP extension 'odbc' is not loaded");

        if (!isset($config['username']))
            return new DibiException("Username must be specified");

        if (!isset($config['password']))
            return new DibiException("Password must be specified");

        if (empty($config['persistent']))
            $conn = @odbc_connect($config['database'], $config['username'], $config['password']);
        else
            $conn = @odbc_pconnect($config['database'], $config['username'], $config['password']);

        if (!is_resource($conn))
            return new DibiException("Connecting error", array(
                'message' => odbc_errormsg(),
                'code'    => odbc_error(),
            ));

        $obj = new self($config);
        $obj->conn = $conn;
        return $obj;
    }



    public function query($sql)
    {
        $this->affectedRows = FALSE;

        $res = @odbc_exec($this->conn, $sql);

        if (is_resource($res))
            return new DibiOdbcResult($res);

        if ($res === FALSE)
            return new DibiException("Query error", $this->errorInfo($sql));

        $this->affectedRows = odbc_num_rows($this->conn);
        if ($this->affectedRows < 0) $this->affectedRows = FALSE;

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
        return odbc_autocommit($this->conn, FALSE);
    }


    public function commit()
    {
        $ok = odbc_commit($this->conn);
        odbc_autocommit($this->conn, TRUE);
        return $ok;
    }


    public function rollback()
    {
        $ok = odbc_rollback($this->conn);
        odbc_autocommit($this->conn, TRUE);
        return $ok;
    }


    private function errorInfo($sql = NULL)
    {
        return array(
            'message'  => odbc_errormsg($this->conn),
            'code'     => odbc_error($this->conn),
            'sql'      => $sql,
        );
    }



    public function escape($value, $appendQuotes = FALSE)
    {
        $value = str_replace("'", "''", $value);
        return $appendQuotes
               ? "'" . $value . "'"
               : $value;
    }


    public function quoteName($value)
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
    }


} // class DibiOdbcDriver







class DibiOdbcResult extends DibiResult
{
    private
        $resource,
        $meta,
        $row = 0;


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
