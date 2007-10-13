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
 * The dibi driver for SQlite database
 *
 */
class DibiSqliteDriver extends DibiDriver
{
    public $formats = array(
        'TRUE'     => "1",
        'FALSE'    => "0",
        'date'     => "U",
        'datetime' => "U",
    );



    /**
     * @param array  connect configuration
     * @throws DibiException
     */
    public function __construct($config)
    {
        if (empty($config['database'])) {
            throw new DibiException("Database must be specified");
        }

        if (!isset($config['mode'])) $config['mode'] = 0666;

        parent::__construct($config);
    }



    protected function connect()
    {
        if (!extension_loaded('sqlite')) {
            throw new DibiException("PHP extension 'sqlite' is not loaded");
        }

        $config = $this->getConfig();

        $errorMsg = '';
        if (empty($config['persistent'])) {
            $connection = @sqlite_open($config['database'], $config['mode'], $errorMsg);
        } else {
            $connection = @sqlite_popen($config['database'], $config['mode'], $errorMsg);
        }

        if (!$connection) {
            throw new DibiDatabaseException($errorMsg);
        }

        dibi::notify('connected', $this);
        return $connection;
    }



    protected function doQuery($sql)
    {
        $connection = $this->getConnection();
        $res = @sqlite_query($connection, $sql, SQLITE_ASSOC);

        if ($res === FALSE) {
            $code = sqlite_last_error($connection);
            throw new DibiDatabaseException(sqlite_error_string($code), $code, $sql);
        }

        return is_resource($res) ? new DibiSqliteResult($res) : TRUE;
    }



    public function affectedRows()
    {
        $rows = sqlite_changes($this->getConnection());
        return $rows < 0 ? FALSE : $rows;
    }



    public function insertId()
    {
        $id = sqlite_last_insert_rowid($this->getConnection());
        return $id < 1 ? FALSE : $id;
    }



    public function begin()
    {
        $this->doQuery('BEGIN');
        dibi::notify('begin', $this);
    }



    public function commit()
    {
        $this->doQuery('COMMIT');
        dibi::notify('commit', $this);
    }



    public function rollback()
    {
        $this->doQuery('ROLLBACK');
        dibi::notify('rollback', $this);
    }



    public function errorInfo()
    {
        $code = sqlite_last_error($this->getConnection());
        return array(
            'message'  => sqlite_error_string($code),
            'code'     => $code,
        );
    }



    public function escape($value, $appendQuotes = TRUE)
    {
        return $appendQuotes
               ? "'" . sqlite_escape_string($value) . "'"
               : sqlite_escape_string($value);
    }



    public function delimite($value)
    {
        return '[' . str_replace('.', '].[', $value) . ']';
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
        $sql .= ' LIMIT ' . $limit . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
    }

} // class DibiSqliteDriver









class DibiSqliteResult extends DibiResult
{

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
