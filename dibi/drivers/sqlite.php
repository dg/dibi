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
 * The dibi driver for SQlite database
 *
 */
class DibiSqliteDriver extends DibiDriver
{
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
        if (!extension_loaded('sqlite'))
            throw new DibiException("PHP extension 'sqlite' is not loaded");

        if (empty($config['database']))
            throw new DibiException("Database must be specified");

        if (!isset($config['mode']))
            $config['mode'] = 0666;

        $errorMsg = '';
        if (empty($config['persistent']))
            $conn = @sqlite_open($config['database'], $config['mode'], $errorMsg);
        else
            $conn = @sqlite_popen($config['database'], $config['mode'], $errorMsg);

        if (!$conn)
            throw new DibiException("Connecting error", array(
                'message' => $errorMsg,
            ));

        $obj = new self($config);
        $obj->conn = $conn;
        return $obj;
    }



    public function nativeQuery($sql)
    {
        $this->insertId = $this->affectedRows = FALSE;

        $res = @sqlite_query($this->conn, $sql, SQLITE_ASSOC);

        if ($res === FALSE) return FALSE;

        $this->affectedRows = sqlite_changes($this->conn);
        if ($this->affectedRows < 0) $this->affectedRows = FALSE;

        $this->insertId = sqlite_last_insert_rowid($this->conn);
        if ($this->insertId < 1) $this->insertId = FALSE;

        if (is_resource($res))
            return new DibiSqliteResult($res);

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
        return sqlite_query($this->conn, 'BEGIN');
    }


    public function commit()
    {
        return sqlite_query($this->conn, 'COMMIT');
    }


    public function rollback()
    {
        return sqlite_query($this->conn, 'ROLLBACK');
    }


    public function errorInfo()
    {
        $code = sqlite_last_error($this->conn);
        return array(
            'message'  => sqlite_error_string($code),
            'code'     => $code,
        );
    }


    public function escape($value, $appendQuotes = FALSE)
    {
        return $appendQuotes
               ? "'" . sqlite_escape_string($value) . "'"
               : sqlite_escape_string($value);
    }


    public function quoteName($value)
    {
        return '[' . $value . ']';
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
        $sql .= ' LIMIT ' . $limit . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
    }

} // class DibiSqliteDriver









class DibiSqliteResult extends DibiResult
{
    private $resource;


    public function __construct($resource)
    {
        $this->resource = $resource;
    }


    public function rowCount()
    {
        return sqlite_num_rows($this->resource);
    }


    protected function doFetch()
    {
        return sqlite_fetch_array($this->resource, SQLITE_ASSOC);
    }


    public function seek($row)
    {
        return sqlite_seek($this->resource, $row);
    }


    protected function free()
    {
    }


    /** this is experimental */
    protected function buildMeta()
    {
        $count = sqlite_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {
            $name = sqlite_field_name($this->resource, $index);
            $this->meta[$name] = array('type' => dibi::FIELD_UNKNOWN);
            $this->convert[$name] = dibi::FIELD_UNKNOWN;
        }
    }


} // class DibiSqliteResult
