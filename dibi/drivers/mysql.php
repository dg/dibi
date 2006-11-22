<?php

/**
 * dibi - Database Abstraction Layer according to dgx
 * --------------------------------------------------
 *
 * This source file is subject to the GNU GPL license.
 *
 * @author     David Grudl aka -dgx- <dave@dgx.cz>
 * @link       http://dibi.texy.info/
 * @copyright  Copyright (c) 2005-2006 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE v2
 * @package    dibi
 * @category   Database
 * @version    $Revision$ $Date$
 */


// security - include dibi.php, not this file
if (!defined('DIBI')) die();


/**
 * The dibi driver for MySQL database
 *
 */
class DibiMySqlDriver extends DibiDriver {
    private
        $conn,
        $insertId = FALSE,
        $affectedRows = FALSE;

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
     * @throw DibiException
     */
    public static function connect($config)
    {
        if (!extension_loaded('mysql'))
            throw new DibiException("PHP extension 'mysql' is not loaded");

        foreach (array('username', 'password', 'protocol') as $var)
            if (!isset($config[$var])) $config[$var] = NULL;

        if (empty($config['host'])) $config['host'] = 'localhost';

        if ($config['protocol'] === 'unix')  // host can be socket
            $host = ':' . $config['host'];
        else
            $host = $config['host'] . (empty($config['port']) ? '' : ':'.$config['port']);

        // some errors aren't handled. Must use $php_errormsg
        if (function_exists('ini_set'))
            $save = ini_set('track_errors', TRUE);
        $php_errormsg = '';

        if (empty($config['persistent']))
            $conn = @mysql_connect($host, $config['username'], $config['password']);
        else
            $conn = @mysql_pconnect($host, $config['username'], $config['password']);

        if (function_exists('ini_set'))
            ini_set('track_errors', $save);


        if (!is_resource($conn))
            throw new DibiException("Connecting error", array(
                'message' => mysql_error() ? mysql_error() : $php_errormsg,
                'code'    => mysql_errno(),
            ));


        if (!empty($config['charset'])) {
            $succ = @mysql_query('SET CHARACTER SET '.$config['charset'], $conn);
            // don't handle this error...
        }


        if (!empty($config['database'])) {
            if (!@mysql_select_db($config['database'], $conn))
                throw new DibiException("Connecting error", array(
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
        $this->insertId = $this->affectedRows = FALSE;
        $res = @mysql_query($sql, $this->conn);

        if (is_resource($res))
            return new DibiMySqlResult($res);

        if ($res === FALSE)
            throw new DibiException("Query error", array(
                'message' => mysql_error($this->conn),
                'code'    => mysql_errno($this->conn),
                'sql'     => $sql,
            ));

        $this->affectedRows = mysql_affected_rows($this->conn);
        if ($this->affectedRows < 0) $this->affectedRows = FALSE;

        $this->insertId = mysql_insert_id($this->conn);
        if ($this->insertId < 1) $this->insertId = FALSE;

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
        return '`' . str_replace('.', '`.`', $value) . '`';
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

        // see http://dev.mysql.com/doc/refman/5.0/en/select.html
        $sql .= ' LIMIT ' . ($limit < 0 ? '18446744073709551615' : (int) $limit)
             . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
    }


/* is this really needed?
    public function getResource()
    {
        return $this->conn;
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
            'ENUM'      => dibi::FIELD_TEXT, // eventually dibi::FIELD_INTEGER
            'SET'       => dibi::FIELD_TEXT,  // eventually dibi::FIELD_INTEGER
            'CHAR'      => dibi::FIELD_TEXT,
            'VARCHAR'   => dibi::FIELD_TEXT,
            'STRING'    => dibi::FIELD_TEXT,
            'TINYTEXT'  => dibi::FIELD_TEXT,
            'TEXT'      => dibi::FIELD_TEXT,
            'MEDIUMTEXT'=> dibi::FIELD_TEXT,
            'LONGTEXT'  => dibi::FIELD_TEXT,
            'BINARY'    => dibi::FIELD_BINARY,
            'VARBINARY' => dibi::FIELD_BINARY,
            'TINYBLOB'  => dibi::FIELD_BINARY,
            'BLOB'      => dibi::FIELD_BINARY,
            'MEDIUMBLOB'=> dibi::FIELD_BINARY,
            'LONGBLOB'  => dibi::FIELD_BINARY,
            'DATE'      => dibi::FIELD_DATE,
            'DATETIME'  => dibi::FIELD_DATETIME,
            'TIMESTAMP' => dibi::FIELD_DATETIME,
            'TIME'      => dibi::FIELD_DATETIME,
            'BIT'       => dibi::FIELD_BOOL,
            'YEAR'      => dibi::FIELD_INTEGER,
            'TINYINT'   => dibi::FIELD_INTEGER,
            'SMALLINT'  => dibi::FIELD_INTEGER,
            'MEDIUMINT' => dibi::FIELD_INTEGER,
            'INT'       => dibi::FIELD_INTEGER,
            'INTEGER'   => dibi::FIELD_INTEGER,
            'BIGINT'    => dibi::FIELD_INTEGER,
            'FLOAT'     => dibi::FIELD_FLOAT,
            'DOUBLE'    => dibi::FIELD_FLOAT,
            'REAL'      => dibi::FIELD_FLOAT,
            'DECIMAL'   => dibi::FIELD_FLOAT,
            'NUMERIC'   => dibi::FIELD_FLOAT,
        );

        $count = mysql_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {

            $info['native'] = $native = strtoupper(mysql_field_type($this->resource, $index));
            $info['flags'] = explode(' ', mysql_field_flags($this->resource, $index));
            $info['length'] = mysql_field_len($this->resource, $index);
            $info['table'] = mysql_field_table($this->resource, $index);

            if (in_array('auto_increment', $info['flags']))  // or 'primary_key' ?
                $info['type'] = dibi::FIELD_COUNTER;
            else {
                $info['type'] = isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN;

//                if ($info['type'] == dibi::FIELD_TEXT && $info['length'] > 255)
//                    $info['type'] = dibi::FIELD_LONG_TEXT;
            }

            $name = mysql_field_name($this->resource, $index);
            $this->meta[$name] = $info;
            $this->convert[$name] = $info['type'];
        }
    }


} // class DibiMySqlResult
