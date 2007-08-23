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
 * The dibi driver for SQlite database
 *
 */
class DibiSqliteDriver extends DibiDriver
{
    private
        $insertId = FALSE,
        $affectedRows = FALSE;

    public
        $formats = array(
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
        if (!extension_loaded('sqlite')) {
            throw new DibiException("PHP extension 'sqlite' is not loaded");
        }

        if (empty($config['database'])) {
            throw new DibiException("Database must be specified (driver sqlite)");
        }

        if (!isset($config['mode'])) $config['mode'] = 0666;

        parent::__construct($config);
    }



    protected function connect()
    {
        $config = $this->config;

        $errorMsg = '';
        if (empty($config['persistent'])) {
            $connection = @sqlite_open($config['database'], $config['mode'], $errorMsg);
        } else {
            $connection = @sqlite_popen($config['database'], $config['mode'], $errorMsg);
        }

        if (!$connection) {
            throw new DibiException("Connecting error (driver sqlite)", array(
                'message' => $errorMsg,
            ));
        }

        return $connection;
    }



    public function nativeQuery($sql)
    {
        $this->insertId = $this->affectedRows = FALSE;

        $connection = $this->getConnection();
        $res = @sqlite_query($connection, $sql, SQLITE_ASSOC);

        if ($res === FALSE) return FALSE;

        $this->affectedRows = sqlite_changes($connection);
        if ($this->affectedRows < 0) $this->affectedRows = FALSE;

        $this->insertId = sqlite_last_insert_rowid($connection);
        if ($this->insertId < 1) $this->insertId = FALSE;

        if (is_resource($res)) {
            return new DibiSqliteResult($res);
        }

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
        return sqlite_query($this->getConnection(), 'BEGIN');
    }



    public function commit()
    {
        return sqlite_query($this->getConnection(), 'COMMIT');
    }



    public function rollback()
    {
        return sqlite_query($this->getConnection(), 'ROLLBACK');
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
