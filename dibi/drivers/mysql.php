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
 * @license    GNU GENERAL PUBLIC LICENSE
 * @package    dibi
 * @category   Database
 * @version    0.5alpha (2006-05-26) for PHP5
 */


// security - include dibi.php, not this file
if (!defined('dibi')) die();


/**
 * The dibi driver for MySQL database
 *
 */
class DibiMySqlDriver extends DibiDriver {
    private
        $conn;

    public
        $formats = array(
            'NULL'     => "NULL",
            'TRUE'     => "1",
            'FALSE'    => "0",
            'date'     => "'Y-m-d'",
            'datetime' => "'Y-m-d H:i:s'",
        );


    /**
     * Driver factory
     */
    public static function connect($config)
    {
        if (!extension_loaded('mysql'))
            return new DibiException("PHP extension 'mysql' is not loaded");


        if (empty($config['host'])) $config['host'] = 'localhost';

        if (@$config['protocol'] === 'unix')  // host can be socket
            $host = ':' . $config['host'];
        else
            $host = $config['host'] . (empty($config['port']) ? '' : $config['port']);


        // some errors aren't handled. Must use $php_errormsg
        if (function_exists('ini_set'))
            $save = ini_set('track_errors', TRUE);
        $php_errormsg = '';

        if (empty($config['persistent']))
            $conn = @mysql_connect($host, @$config['username'], @$config['password']);
        else
            $conn = @mysql_pconnect($host, @$config['username'], @$config['password']);

        if (function_exists('ini_set'))
            ini_set('track_errors', $save);


        if (!is_resource($conn))
            return new DibiException("Connecting error", array(
                'message' => mysql_error() ? mysql_error() : $php_errormsg,
                'code'    => mysql_errno(),
            ));


        if (!empty($config['charset'])) {
            $succ = @mysql_query('SET CHARACTER SET '.$config['charset'], $conn);
            // don't handle this error...
        }


        if (!empty($config['database'])) {
            if (!@mysql_select_db($config['database'], $conn))
                return new DibiException("Connecting error", array(
                    'message' => mysql_error($conn),
                    'code'    => mysql_errno($conn),
                ));
        }


        $obj = new self($config);
        $obj->conn = $conn;
        return $obj;
    }



    public function query($sql)
    {
        $res = @mysql_query($sql, $this->conn);

        if (is_resource($res))
            return new DibiMySqlResult($res);

        if ($res === FALSE)
            return new DibiException("Query error", array(
                'message' => mysql_error($this->conn),
                'code'    => mysql_errno($this->conn),
                'sql'     => $sql,
            ));

        return TRUE;
    }


    public function affectedRows()
    {
        $rows = mysql_affected_rows($this->conn);
        return $rows < 0 ? FALSE : $rows;
    }


    public function insertId()
    {
        $id = mysql_insert_id($this->conn);
        return $id < 0 ? FALSE : $id;
    }


    public function begin()
    {
        return mysql_query('BEGIN', $this->conn);
    }


    public function commit()
    {
        return mysql_query('COMMIT', $this->conn);
    }


    public function rollback()
    {
        return mysql_query('ROLLBACK', $this->conn);
    }


    public function escape($value, $appendQuotes = FALSE)
    {
        return $appendQuotes
               ? "'" . mysql_real_escape_string($value, $this->conn) . "'"
               : mysql_real_escape_string($value, $this->conn);
    }


    public function quoteName($value)
    {
        return '`' . strtr($value, array('.' => '`.`')) . '`';
    }


    public function getMetaData()
    {
        trigger_error('Meta is not implemented yet.', E_USER_WARNING);
    }


/*
    // is this really needed?
    public function getResource()
    {
        return $this->conn;
    }

    // experimental
    public function applyLimit(&$sql, $offset, $limit)
    {
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit . ($offset > 0 ? " OFFSET " . (int) $offset : "");
        } elseif ($offset > 0) {
            $sql .= " LIMIT " . $offset . ", 18446744073709551615";
        }
    }
*/

}  // DibiMySqlDriver









class DibiMySqlResult extends DibiResult
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
        return mysql_num_rows($this->resource);
    }


    protected function doFetch()
    {
        return mysql_fetch_assoc($this->resource);
    }


    public function seek($row)
    {
        return mysql_data_seek($this->resource, $row);
    }


    protected function free()
    {
        mysql_free_result($this->resource);
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
            'ENUM'      => self::FIELD_TEXT, // eventually self::FIELD_INTEGER
            'SET'       => self::FIELD_TEXT,  // eventually self::FIELD_INTEGER
            'CHAR'      => self::FIELD_TEXT,
            'VARCHAR'   => self::FIELD_TEXT,
            'STRING'    => self::FIELD_TEXT,
            'TINYTEXT'  => self::FIELD_TEXT,
            'TEXT'      => self::FIELD_TEXT,
            'MEDIUMTEXT'=> self::FIELD_TEXT,
            'LONGTEXT'  => self::FIELD_TEXT,
            'BINARY'    => self::FIELD_BINARY,
            'VARBINARY' => self::FIELD_BINARY,
            'TINYBLOB'  => self::FIELD_BINARY,
            'BLOB'      => self::FIELD_BINARY,
            'MEDIUMBLOB'=> self::FIELD_BINARY,
            'LONGBLOB'  => self::FIELD_BINARY,
            'DATE'      => self::FIELD_DATE,
            'DATETIME'  => self::FIELD_DATETIME,
            'TIMESTAMP' => self::FIELD_DATETIME,
            'TIME'      => self::FIELD_DATETIME,
            'BIT'       => self::FIELD_BOOL,
            'YEAR'      => self::FIELD_INTEGER,
            'TINYINT'   => self::FIELD_INTEGER,
            'SMALLINT'  => self::FIELD_INTEGER,
            'MEDIUMINT' => self::FIELD_INTEGER,
            'INT'       => self::FIELD_INTEGER,
            'INTEGER'   => self::FIELD_INTEGER,
            'BIGINT'    => self::FIELD_INTEGER,
            'FLOAT'     => self::FIELD_FLOAT,
            'DOUBLE'    => self::FIELD_FLOAT,
            'REAL'      => self::FIELD_FLOAT,
            'DECIMAL'   => self::FIELD_FLOAT,
            'NUMERIC'   => self::FIELD_FLOAT,
        );

        $count = mysql_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {

            $info['native'] = $native = strtoupper(mysql_field_type($this->resource, $index));
            $info['flags'] = explode(' ', mysql_field_flags($this->resource, $index));
            $info['length'] = mysql_field_len($this->resource, $index);
            $info['table'] = mysql_field_table($this->resource, $index);

            if (in_array('auto_increment', $info['flags']))  // or 'primary_key' ?
                $info['type'] = self::FIELD_COUNTER;
            else {
                $info['type'] = isset($types[$native]) ? $types[$native] : self::FIELD_UNKNOWN;

//                if ($info['type'] == self::FIELD_TEXT && $info['length'] > 255)
//                    $info['type'] = self::FIELD_LONG_TEXT;
            }

            $name = mysql_field_name($this->resource, $index);
            $this->meta[$name] = $info;
            $this->convert[$name] = $info['type'];
        }
    }


} // class DibiMySqlResult





?>