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
 * The dibi driver for MySQL database
 *
 */
class DibiMySqlDriver extends DibiDriver
{
    private
        $insertId = FALSE,
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
        if (!extension_loaded('mysql'))
            throw new DibiException("PHP extension 'mysql' is not loaded");

        // default values
        if (empty($config['username'])) $config['username'] = ini_get('mysql.default_user');
        if (empty($config['password'])) $config['password'] = ini_get('mysql.default_password');
        if (empty($config['host'])) {
            $config['host'] = ini_get('mysql.default_host');
            if (empty($config['port'])) ini_get('mysql.default_port');
            if (empty($config['host'])) $config['host'] = 'localhost';
        }

        parent::__construct($config);
    }



    protected function connect()
    {
        $config = $this->config;

        if (isset($config['protocol']) && $config['protocol'] === 'unix')  // host can be socket
            $host = ':' . $config['host'];
        else
            $host = $config['host'] . (empty($config['port']) ? '' : ':'.$config['port']);

        // some errors aren't handled. Must use $php_errormsg
        if (function_exists('ini_set'))
            $save = ini_set('track_errors', TRUE);
        $php_errormsg = '';

        if (empty($config['persistent']))
            $connection = @mysql_connect($host, $config['username'], $config['password'], TRUE);
        else
            $connection = @mysql_pconnect($host, $config['username'], $config['password']);

        if (function_exists('ini_set'))
            ini_set('track_errors', $save);


        if (!is_resource($connection))
            throw new DibiException("Connecting error (driver mysql)'", array(
                'message' => mysql_error() ? mysql_error() : $php_errormsg,
                'code'    => mysql_errno(),
            ));


        if (!empty($config['charset'])) {
            @mysql_query("SET NAMES '" . $config['charset'] . "'", $connection);
            // don't handle this error...
        }


        if (!empty($config['database'])) {
            if (!@mysql_select_db($config['database'], $connection))
                throw new DibiException("Connecting error (driver mysql)", array(
                    'message' => mysql_error($connection),
                    'code'    => mysql_errno($connection),
                ));
        }

        return $connection;
    }



    public function nativeQuery($sql)
    {
        $this->insertId = $this->affectedRows = FALSE;
        $connection = $this->getConnection();
        $res = @mysql_query($sql, $connection);

        if ($res === FALSE) return FALSE;

        $this->affectedRows = mysql_affected_rows($connection);
        if ($this->affectedRows < 0) $this->affectedRows = FALSE;

        $this->insertId = mysql_insert_id($connection);
        if ($this->insertId < 1) $this->insertId = FALSE;

        if (is_resource($res))
            return new DibiMySqlResult($res);

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
        return mysql_query('BEGIN', $this->getConnection());
    }



    public function commit()
    {
        return mysql_query('COMMIT', $this->getConnection());
    }



    public function rollback()
    {
        return mysql_query('ROLLBACK', $this->getConnection());
    }



    public function errorInfo()
    {
        $connection = $this->getConnection();
        return array(
            'message'  => mysql_error($connection),
            'code'     => mysql_errno($connection),
        );
    }



    public function escape($value, $appendQuotes = TRUE)
    {
        $connection = $this->getConnection();
        return $appendQuotes
               ? "'" . mysql_real_escape_string($value, $connection) . "'"
               : mysql_real_escape_string($value, $connection);
    }



    public function delimite($value)
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


}  // DibiMySqlDriver









class DibiMySqlResult extends DibiResult
{
    private $resource;


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



    /** this is experimental */
    protected function buildMeta()
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

//                if ($info['type'] === dibi::FIELD_TEXT && $info['length'] > 255)
//                    $info['type'] = dibi::FIELD_LONG_TEXT;
            }

            $name = mysql_field_name($this->resource, $index);
            $this->meta[$name] = $info;
            $this->convert[$name] = $info['type'];
        }
    }


} // class DibiMySqlResult
