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
 * The dibi driver for SQlite database
 *
 */
class DibiSqliteDriver extends DibiDriver {
    private
        $conn,
        $insertId = FALSE,
        $affectedRows = FALSE;

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
        if (!extension_loaded('sqlite'))
            return new DibiException("PHP extension 'sqlite' is not loaded");

        if (empty($config['database']))
            return new DibiException("Database must be specified");

        if (!isset($config['mode']))
            $config['mode'] = 0666;

        $errorMsg = '';
        if (empty($config['persistent']))
            $conn = @sqlite_open($config['database'], $config['mode'], $errorMsg);
        else
            $conn = @sqlite_popen($config['database'], $config['mode'], $errorMsg);

        if (!$conn)
            return new DibiException("Connecting error", array(
                'message' => $errorMsg,
            ));

        $obj = new self($config);
        $obj->conn = $conn;
        return $obj;
    }



    public function query($sql)
    {
        $this->insertId = $this->affectedRows = FALSE;

        $errorMsg = '';
        $res = @sqlite_query($this->conn, $sql, SQLITE_ASSOC, $errorMsg);

        if ($res === FALSE)
            return new DibiException("Query error", array(
                'message' => $errorMsg,
                'sql'      => $sql,
            ));

        if (is_resource($res))
            return new DibiSqliteResult($res);

        $this->affectedRows = sqlite_changes($this->conn);
        if ($this->affectedRows < 0) $this->affectedRows = FALSE;

        $this->insertId = sqlite_last_insert_rowid($this->conn);
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


    public function escape($value, $appendQuotes = FALSE)
    {
        return $appendQuotes
               ? "'" . sqlite_escape_string($value) . "'"
               : sqlite_escape_string($value);
    }


    public function quoteName($value)
    {
        return $this->applySubsts($value);
    }



    public function getMetaData()
    {
        trigger_error('Meta is not implemented yet.', E_USER_WARNING);
    }



} // class DibiSqliteDriver









class DibiSqliteResult extends DibiResult
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
        $count = sqlite_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {
            $name = sqlite_field_name($this->resource, $index);
            $this->meta[$name] = array('type' => dibi::FIELD_UNKNOWN);
            $this->convert[$name] = dibi::FIELD_UNKNOWN;
        }
    }


} // class DibiSqliteResult





?>