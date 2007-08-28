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


// security - include dibi.php, not this file
if (!class_exists('dibi', FALSE)) die();


/**
 * The dibi driver for PDO
 *
 */
class DibiPdoDriver extends DibiDriver
{
    public
        $formats = array(
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
        if (!extension_loaded('pdo')) {
            throw new DibiException("PHP extension 'pdo' is not loaded");
        }

        if (empty($config['dsn'])) {
            throw new DibiException("DSN must be specified (driver odbc)");
        }

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
        // TODO: or exec() ?
        $res = $this->getConnection()->query($sql);

        if ($res === FALSE) {
            return FALSE;

        } elseif ($res instanceof PDOStatement) {
            return new DibiPdoResult($res);

        } else {
            return TRUE;
        }
    }



    public function affectedRows()
    {
        // not implemented
    }



    public function insertId()
    {
        return $this->getConnection()->lastInsertId();
    }



    public function begin()
    {
        return $this->getConnection()->beginTransaction();
    }



    public function commit()
    {
        return $this->getConnection()->commit();
    }



    public function rollback()
    {
        return $this->getConnection()->rollBack();
    }



    public function errorInfo()
    {
        $error = $this->getConnection()->errorInfo();
        return array(
            'message'  => $error[2],
            'code'     => $error[1],
            'SQLSTATE '=> $error[0],
        );
    }



    public function escape($value, $appendQuotes = TRUE)
    {
        if (!$appendQuotes) {
            throw new DibiException('Escaping without qoutes is not supported by PDO');
        }
        return $this->getConnection()->quote($value);
    }



    public function delimite($value)
    {
        // quoting is not supported by PDO
        return $value;
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
        throw new DibiException(__METHOD__ . ' is not implemented');
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
