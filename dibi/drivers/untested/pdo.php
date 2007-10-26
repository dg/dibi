<?php

/**
 * This file is part of the "dibi" project (http://php7.org/dibi/)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005-2007 David Grudl aka -dgx- (http://www.dgx.cz)
 * @license    New BSD License
 * @version    $Revision$ $Date$
 * @category   Database
 * @package    Dibi
 */


/**
 * The dibi driver for PDO
 *
 */
class DibiPdoDriver extends DibiDriver
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
        self::prepare($config, 'username', 'user');
        self::prepare($config, 'password', 'pass');
        self::prepare($config, 'dsn');
        parent::__construct($config);
    }



    protected function connect()
    {
        if (!extension_loaded('pdo')) {
            throw new DibiException("PHP extension 'pdo' is not loaded");
        }

        $config = $this->getConfig();
        $connection = new PDO($config['dsn'], $config['username'], $config['password']);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        dibi::notify('connected', $this);
        return $connection;
    }



    protected function doQuery($sql)
    {
        $res = $this->getConnection()->query($sql);
        return $res instanceof PDOStatement ? new DibiPdoResult($res) : TRUE;
    }



    public function affectedRows()
    {
        throw new DibiException(__METHOD__ . ' is not implemented');
    }



    public function insertId()
    {
        return $this->getConnection()->lastInsertId();
    }



    public function begin()
    {
        $this->getConnection()->beginTransaction();
        dibi::notify('begin', $this);
    }



    public function commit()
    {
        $this->getConnection()->commit();
        dibi::notify('commit', $this);
    }



    public function rollback()
    {
        $this->getConnection()->rollBack();
        dibi::notify('rollback', $this);
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
    private $row = 0;


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
