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
 * The dibi driver for PostgreSql database
 *
 */
class DibiPostgreDriver extends DibiDriver
{
    public $formats = array(
        'TRUE'     => "1",
        'FALSE'    => "0",
        'date'     => "'Y-m-d'",
        'datetime' => "'Y-m-d H:i:s'",
    );

    /**
     * Affected rows
     * @var mixed
     */
    private $affectedRows = FALSE;



    /**
     * @param array  connect configuration
     * @throws DibiException
     */
    public function __construct($config)
    {
        self::prepare($config, 'database', 'string');
        self::prepare($config, 'type');
        parent::__construct($config);
    }



    protected function connect()
    {
        if (!extension_loaded('pgsql')) {
            throw new DibiException("PHP extension 'pgsql' is not loaded");
        }

        $config = $this->getConfig();

        if (isset($config['persistent'])) {
            $connection = @pg_connect($config['database'], $config['type']);
        } else {
            $connection = @pg_pconnect($config['database'], $config['type']);
        }

        if (!is_resource($connection)) {
            throw new DibiDatabaseException(pg_last_error());
        }

        if (isset($config['charset'])) {
            @pg_set_client_encoding($connection, $config['charset']);
            // don't handle this error...
        }

        dibi::notify('connected', $this);
        return $connection;
    }



    public function nativeQuery($sql)
    {
        $this->affectedRows = FALSE;
        $res = parent::nativeQuery($sql);
        if ($res instanceof DibiResult) {
            $this->affectedRows = pg_affected_rows($res->getResource());
            if ($this->affectedRows < 0) $this->affectedRows = FALSE;
        }
        return $res;
    }



    protected function doQuery($sql)
    {
        $connection = $this->getConnection();
        $res = @pg_query($connection, $sql);

        if ($res === FALSE) {
            throw new DibiDatabaseException(pg_last_error($connection), 0, $sql);
        }

        return is_resource($res) ? new DibiPostgreResult($res) : TRUE;
    }



    public function affectedRows()
    {
        return $this->affectedRows;
    }



    public function insertId($sequence = NULL)
    {
        if ($sequence === NULL) {
            // PostgreSQL 8.1 is needed
            $res = $this->doQuery("SELECT LASTVAL() AS seq");
        } else {
            $res = $this->doQuery("SELECT CURRVAL('$sequence') AS seq");
        }

        if (is_resource($res)) {
            $row = pg_fetch_assoc($res);
            pg_free_result($res);
            return $row['seq'];
        }

        return FALSE;
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
        $value = str_replace('"', '""', $value);
        return '"' . str_replace('.', '"."', $value) . '"';
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
        if ($limit >= 0)
            $sql .= ' LIMIT ' . (int) $limit;

        if ($offset > 0)
            $sql .= ' OFFSET ' . (int) $offset;
    }


} // class DibiPostgreDriver









class DibiPostgreResult extends DibiResult
{

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
