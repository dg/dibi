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
 * The dibi driver for PDO
 *
 */
class DibiPdoDriver extends DibiDriver
{
    /** @var PDO */
    private $conn;

    private $affectedRows = FALSE,

    private $errorMsg;

    public
        $formats = array(
            'TRUE'     => "1",
            'FALSE'    => "0",
            'date'     => "'Y-m-d'",
            'datetime' => "'Y-m-d H:i:s'",
        );



    public static function connect($config)
    {
        if (!extension_loaded('pdo'))
            throw new DibiException("PHP extension 'pdo' is not loaded");

        if (empty($config['dsn']))
            throw new DibiException("DSN must be specified");

        if (empty($config['username'])) $config['username'] = NULL;
        if (empty($config['password'])) $config['password'] = NULL;

        $conn = new PDO($config['dsn'], $config['username'], $config['password']);

        $obj = new self($config);
        $obj->conn = $conn;
        return $obj;
    }



    public function nativeQuery($sql)
    {
        $this->affectedRows = FALSE;

        $this->errorMsg = '';
        $res = $this->conn->query($sql);

        if ($res === FALSE) return FALSE;

        //$this->affectedRows = 0;
        //if ($this->affectedRows < 0) $this->affectedRows = FALSE;

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
        return $this->conn->lastInsertId();
    }


    public function begin()
    {
        return $this->conn->beginTransaction();
    }


    public function commit()
    {
        return $this->conn->commit();
    }


    public function rollback()
    {
        return $this->conn->rollBack();
    }


    public function errorInfo()
    {
        $error = $this->conn->errorInfo();
        return array(
            'message'  => $error[2],
            'code'     => $error[1],
            'SQLSTATE '=> $error[0],
        );
    }


    public function escape($value, $appendQuotes = FALSE)
    {
        return $appendQuotes
               ? $this->conn->quote($value)
               : FALSE; // error
    }


    public function quoteName($value)
    {
        return FALSE; // error
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

} // class DibiPdoDriver









class DibiPdoResult extends DibiResult
{
    /** @var PDOStatement */
    private $resource;

    private $row = 0;


    public function __construct($resource)
    {
        $this->resource = $resource;
    }


    public function rowCount()
    {
        return $this->resource->rowCount();
    }


    protected function doFetch()
    {
        return $this->resource->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT, $this->row++);
    }


    public function seek($row)
    {
        $this->row = $row;
    }


    protected function free()
    {
    }


    /** this is experimental */
    protected function buildMeta()
    {
        $count = $this->resource->columnCount();
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {
            $meta = $this->resource->getColumnMeta($i);
            $meta['type'] = dibi::FIELD_UNKNOWN;
            $name = $meta['name'];
            $this->meta[$name] =  $meta;
            $this->convert[$name] = $meta['type'];
        }
    }


} // class DibiPdoResult
