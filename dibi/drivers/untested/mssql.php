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
 * The dibi driver for MS SQL database
 *
 */
class DibiMsSqlDriver extends DibiDriver
{
    public $formats = array(
        'TRUE'     => "1",
        'FALSE'    => "0",
        'date'     => "'Y-m-d'",
        'datetime' => "'Y-m-d H:i:s'",
    );


    /**
     * @param array  connect configuration
     */
    public function __construct($config)
    {
        if (!isset($config['host'])) $config['host'] = NULL;
        if (!isset($config['username'])) $config['username'] = NULL;
        if (!isset($config['password'])) $config['password'] = NULL;

        parent::__construct($config);
    }



    protected function connect()
    {
        if (!extension_loaded('mssql')) {
            throw new DibiException("PHP extension 'mssql' is not loaded");
        }

        $config = $this->getConfig();

        if (empty($config['persistent'])) {
            $connection = @mssql_connect($config['host'], $config['username'], $config['password'], TRUE);
        } else {
            $connection = @mssql_pconnect($config['host'], $config['username'], $config['password']);
        }

        if (!is_resource($connection)) {
            throw new DibiDatabaseException("Can't connect to DB");
        }

        if (!empty($config['database']) && !@mssql_select_db($config['database'], $connection)) {
            throw new DibiDatabaseException("Can't select DB '$config[database]'");
        }

        dibi::notify('connected', $this);
        return $connection;
    }



    protected function doQuery($sql)
    {
        $res = @mssql_query($sql, $this->getConnection());

        if ($res === FALSE) {
            throw new DibiDatabaseException('Query error', 0, $sql);
        }

        return is_resource($res) ? new DibiMSSqlResult($res) : TRUE;
    }



    public function affectedRows()
    {
        $rows = mssql_rows_affected($this->getConnection());
        return $rows < 0 ? FALSE : $rows;
    }



    public function insertId()
    {
        throw new DibiException(__METHOD__ . ' is not implemented');
    }



    public function begin()
    {
        $this->doQuery('BEGIN TRANSACTION');
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
        return array(
            'message'  => NULL,
            'code'     => NULL,
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

        if ($offset) {
            throw new DibiException('Offset is not implemented');
        }
    }


}  // DibiMsSqlDriver









class DibiMSSqlResult extends DibiResult
{

    public function rowCount()
    {
        return mssql_num_rows($this->resource);
    }



    protected function doFetch()
    {
        return mssql_fetch_assoc($this->resource);
    }



    public function seek($row)
    {
        return mssql_data_seek($this->resource, $row);
    }



    protected function free()
    {
        mssql_free_result($this->resource);
    }



    /** this is experimental */
    protected function buildMeta()
    {
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

        $count = mssql_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {

            $tmp = mssql_fetch_field($this->resource, $index);
            $type = strtoupper($tmp->type);
            $info['native'] = $tmp->type;
            $info['type'] = isset($types[$type]) ? $types[$type] : dibi::FIELD_UNKNOWN;
            $info['length'] = $tmp->max_length;
            $info['table'] = $tmp->column_source;

            $this->meta[$tmp->name] = $info;
            $this->convert[$tmp->name] = $info['type'];
        }
    }


} // class DibiMSSqlResult
