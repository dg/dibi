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
 * The dibi driver for PostgreSql database
 *
 */
class DibiPostgreDriver extends DibiDriver
{
    private
        $affectedRows = FALSE;

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
        if (!extension_loaded('pgsql')) {
            throw new DibiException("PHP extension 'pgsql' is not loaded");
        }

        if (empty($config['string'])) {
            throw new DibiException("Connection string must be specified (driver postgre)");
        }

        if (empty($config['type'])) $config['type'] = NULL;

        parent::__construct($config);
    }



    protected function connect()
    {
        $config = $this->config;

        if (isset($config['persistent'])) {
            $connection = @pg_connect($config['string'], $config['type']);
        } else {
            $connection = @pg_pconnect($config['string'], $config['type']);
        }

        if (!is_resource($connection)) {
            throw new DibiException("Connecting error (driver postgre)", array(
                'message' => pg_last_error(),
            ));
        }

        if (!empty($config['charset'])) {
            @pg_set_client_encoding($connection, $config['charset']);
            // don't handle this error...
        }
        return $connection;
    }



    public function nativeQuery($sql)
    {
        $this->affectedRows = FALSE;

        $res = @pg_query($this->getConnection(), $sql);

        if ($res === FALSE) {
            return FALSE;

        } elseif (is_resource($res)) {
            $this->affectedRows = pg_affected_rows($res);
            if ($this->affectedRows < 0) $this->affectedRows = FALSE;

            return new DibiPostgreResult($res);

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
        return FALSE;
    }



    public function begin()
    {
        return pg_query($this->getConnection(), 'BEGIN');
    }



    public function commit()
    {
        return pg_query($this->getConnection(), 'COMMIT');
    }



    public function rollback()
    {
        return pg_query($this->getConnection(), 'ROLLBACK');
    }



    public function errorInfo()
    {
        return array(
            'message'  => pg_last_error($this->getConnection()),
            'code'     => NULL,
        );
    }



    public function escape($value, $appendQuotes = TRUE)
    {
        return $appendQuotes
               ? "'" . pg_escape_string($value) . "'"
               : pg_escape_string($value);
    }



    public function delimite($value)
    {
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
        if ($limit >= 0)
            $sql .= ' LIMIT ' . (int) $limit;

        if ($offset > 0)
            $sql .= ' OFFSET ' . (int) $offset;
    }


} // class DibiPostgreDriver









class DibiPostgreResult extends DibiResult
{
    private $resource;


    public function __construct($resource)
    {
        $this->resource = $resource;
    }



    public function rowCount()
    {
        return pg_num_rows($this->resource);
    }



    protected function doFetch()
    {
        return pg_fetch_array($this->resource, NULL, PGSQL_ASSOC);
    }



    public function seek($row)
    {
        return pg_result_seek($this->resource, $row);
    }



    protected function free()
    {
        pg_free_result($this->resource);
    }



    /** this is experimental */
    protected function buildMeta()
    {
        static $types = array(
            'bool'      => dibi::FIELD_BOOL,
            'int2'      => dibi::FIELD_INTEGER,
            'int4'      => dibi::FIELD_INTEGER,
            'int8'      => dibi::FIELD_INTEGER,
            'numeric'   => dibi::FIELD_FLOAT,
            'float4'    => dibi::FIELD_FLOAT,
            'float8'    => dibi::FIELD_FLOAT,
            'timestamp' => dibi::FIELD_DATETIME,
            'date'      => dibi::FIELD_DATE,
            'time'      => dibi::FIELD_DATETIME,
            'varchar'   => dibi::FIELD_TEXT,
            'bpchar'    => dibi::FIELD_TEXT,
            'inet'      => dibi::FIELD_TEXT,
            'money'     => dibi::FIELD_FLOAT,
        );

        $count = pg_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {

            $info['native'] = $native = pg_field_type($this->resource, $index);
            $info['length'] = pg_field_size($this->resource, $index);
            $info['table'] = pg_field_table($this->resource, $index);
            $info['type'] = isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN;

            $name = pg_field_name($this->resource, $index);
            $this->meta[$name] = $info;
            $this->convert[$name] = $info['type'];
        }
    }


} // class DibiPostgreResult
