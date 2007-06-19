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
 * The dibi driver for PDO
 *
 */
class DibiPdoDriver extends DibiDriver
{
    private $affectedRows = FALSE;

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
        if (!extension_loaded('pdo'))
            throw new DibiException("PHP extension 'pdo' is not loaded");

        if (empty($config['dsn']))
            throw new DibiException("DSN must be specified (driver odbc)");

        if (empty($config['username'])) $config['username'] = NULL;
        if (empty($config['password'])) $config['password'] = NULL;

        parent::__construct($config);
    }



    protected function connect()
    {
        return new PDO($this->config['dsn'], $this->config['username'], $this->config['password']);
    }



    public function nativeQuery($sql)
    {
        $this->affectedRows = FALSE;

        // TODO: or exec() ?
        $res = $this->getResource()->query($sql);

        if ($res === FALSE) return FALSE;

        if ($res instanceof PDOStatement)
            return new DibiPdoResult($res);

        return TRUE;
    }


    public function affectedRows()
    {
        return $this->affectedRows;
    }


    public function insertId()
    {
        return $this->getResource()->lastInsertId();
    }


    public function begin()
    {
        return $this->getResource()->beginTransaction();
    }


    public function commit()
    {
        return $this->getResource()->commit();
    }


    public function rollback()
    {
        return $this->getResource()->rollBack();
    }


    public function errorInfo()
    {
        $error = $this->getResource()->errorInfo();
        return array(
            'message'  => $error[2],
            'code'     => $error[1],
            'SQLSTATE '=> $error[0],
        );
    }


    public function escape($value, $appendQuotes=TRUE)
    {
        if (!$appendQuotes) {
            trigger_error('dibi: escaping without qoutes is not supported by PDO', E_USER_WARNING);
            return NULL;
        }
        return $this->getResource()->quote($value);
    }


    public function delimite($value)
    {
        // quoting is not supported by PDO
        return $value;
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
        trigger_error('Meta is not implemented.', E_USER_WARNING);
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
            $meta = $this->resource->getColumnMeta($index);
            // TODO:
            $meta['type'] = dibi::FIELD_UNKNOWN;
            $name = $meta['name'];
            $this->meta[$name] =  $meta;
            $this->convert[$name] = $meta['type'];
        }
    }


} // class DibiPdoResult
