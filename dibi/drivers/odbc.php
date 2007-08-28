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
 * The dibi driver interacting with databases via ODBC connections
 *
 */
class DibiOdbcDriver extends DibiDriver
{
    private
        $affectedRows = FALSE;

    public
        $formats = array(
            'TRUE'     => "-1",
            'FALSE'    => "0",
            'date'     => "#m/d/Y#",
            'datetime' => "#m/d/Y H:i:s#",
        );



    /**
     * @param array  connect configuration
     * @throws DibiException
     */
    public function __construct($config)
    {
        if (!extension_loaded('odbc'))
            throw new DibiException("PHP extension 'odbc' is not loaded");

        // default values
        if (empty($config['username'])) $config['username'] = ini_get('odbc.default_user');
        if (empty($config['password'])) $config['password'] = ini_get('odbc.default_pw');
        if (empty($config['database'])) $config['database'] = ini_get('odbc.default_db');

        if (empty($config['username'])) {
            throw new DibiException("Username must be specified (driver odbc)");
        }

        if (empty($config['password'])) {
            throw new DibiException("Password must be specified (driver odbc)");
        }

        if (empty($config['database'])) {
            throw new DibiException("Database must be specified (driver odbc)");
        }

        parent::__construct($config);
    }



    protected function connect()
    {
        $config = $this->config;

        if (empty($config['persistent'])) {
            $connection = @odbc_connect($config['database'], $config['username'], $config['password']);
        } else {
            $connection = @odbc_pconnect($config['database'], $config['username'], $config['password']);
        }

        if (!is_resource($connection)) {
            throw new DibiException("Connecting error (driver odbc)", array(
                'message' => odbc_errormsg(),
                'code'    => odbc_error(),
            ));
        }

        return $connection;
    }



    public function nativeQuery($sql)
    {
        $this->affectedRows = FALSE;
        $res = @odbc_exec($this->getConnection(), $sql);

        if ($res === FALSE) {
            return FALSE;

        } elseif (is_resource($res)) {
            $this->affectedRows = odbc_num_rows($res);
            if ($this->affectedRows < 0) $this->affectedRows = FALSE;

            return new DibiOdbcResult($res);

        } else {
            return TRUE;
        }
    }



    public function affectedRows()
    {
        return $this->affectedRows;
    }



    public function insertId()
    {
        throw new DibiException(__METHOD__ . ' is not implemented');
    }



    public function begin()
    {
        return odbc_autocommit($this->getConnection(), FALSE);
    }



    public function commit()
    {
        $connection = $this->getConnection();
        $ok = odbc_commit($connection);
        odbc_autocommit($connection, TRUE);
        return $ok;
    }



    public function rollback()
    {
        $connection = $this->getConnection();
        $ok = odbc_rollback($connection);
        odbc_autocommit($connection, TRUE);
        return $ok;
    }



    public function errorInfo()
    {
        $connection = $this->getConnection();
        return array(
            'message'  => odbc_errormsg($connection),
            'code'     => odbc_error($connection),
        );
    }



    public function escape($value, $appendQuotes = TRUE)
    {
        $value = str_replace("'", "''", $value);
        return $appendQuotes
               ? "'" . $value . "'"
               : $value;
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
        // offset suppot is missing...
        if ($limit >= 0) {
           $sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ')';
        }

        if ($offset) throw new DibiException('Offset is not implemented in driver odbc');
    }


} // class DibiOdbcDriver







class DibiOdbcResult extends DibiResult
{
    private $resource;
    private $row = 0;


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



    /** this is experimental */
    protected function buildMeta()
    {
        // cache
        if ($this->meta !== NULL) {
            return $this->meta;
        }

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
