<?php

/**
 * dibi - Database Abstraction Layer according to dgx
 * --------------------------------------------------
 *
 * This source file is subject to the GNU GPL license.
 *
 * @author     David Grudl aka -dgx- <dave@dgx.cz>
 * @link       http://texy.info/dibi/
 * @copyright  Copyright (c) 2005-2006 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE v2
 * @package    dibi
 * @category   Database
 * @version    $Revision: 17 $ $Date: 2006-08-25 20:10:30 +0200 (pá, 25 VIII 2006) $
 */


// security - include dibi.php, not this file
if (!defined('DIBI')) die();


/**
 * The dibi driver for PostgreSql database
 *
 */
class DibiPostgreDriver extends DibiDriver {
    private
        $conn,
        $affectedRows = FALSE;

    public
        $formats = array(
            'NULL'     => "NULL",
            'TRUE'     => "1",
            'FALSE'    => "0",
            'date'     => "'Y-m-d'",
            'datetime' => "'Y-m-d H:i:s'",
        );



    public static function connect($config)
    {
        if (!extension_loaded('pgsql'))
            throw new DibiException("PHP extension 'pgsql' is not loaded");

        if (empty($config['string']))
            throw new DibiException("Connection string must be specified");

        if (empty($config['type'])) $config['type'] = NULL;

        $errorMsg = '';
        if (isset($config['persistent']))
            $conn = @pg_connect($config['string'], $config['type']);
        else
            $conn = @pg_pconnect($config['string'], $config['type']);

        if (!is_resource($conn))
            throw new DibiException("Connecting error", array(
                'message' => pg_last_error(),
            ));

        if (!empty($config['charset'])) {
            $succ = @pg_set_client_encoding($conn, $config['charset']);
            // don't handle this error...
        }

        $obj = new self($config);
        $obj->conn = $conn;
        return $obj;
    }



    public function query($sql)
    {
        $this->affectedRows = FALSE;

        $errorMsg = '';
        $res = @pg_query($this->conn, $sql);

        if (is_resource($res))
            return new DibiPostgreResult($res);

        if ($res === FALSE)
            return new DibiException("Query error", array(
                'message' => pg_last_error($this->conn),
                'sql'     => $sql,
            ));

        $this->affectedRows = pg_affected_rows($this->conn);
        if ($this->affectedRows < 0) $this->affectedRows = FALSE;

        return TRUE;
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
        return pg_query($this->conn, 'BEGIN');
    }


    public function commit()
    {
        return pg_query($this->conn, 'COMMIT');
    }


    public function rollback()
    {
        return pg_query($this->conn, 'ROLLBACK');
    }


    public function escape($value, $appendQuotes = FALSE)
    {
        return $appendQuotes
               ? "'" . pg_escape_string($value) . "'"
               : pg_escape_string($value);
    }


    public function quoteName($value)
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
    private
        $resource,
        $meta;


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


    public function getFields()
    {
        // cache
        if ($this->meta === NULL)
            $this->createMeta();

        return array_keys($this->meta);
    }


    protected function detectTypes()
    {
        if ($this->meta === NULL)
            $this->createMeta();
    }


    /** this is experimental */
    public function getMetaData($field)
    {
        // cache
        if ($this->meta === NULL)
            $this->createMeta();

        return isset($this->meta[$field]) ? $this->meta[$field] : FALSE;
    }


    /** this is experimental */
    private function createMeta()
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
