<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2007 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://php7.org/dibi/
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  (dibi license)
 * @category   Database
 * @package    Dibi
 * @link       http://php7.org/dibi/
 */


/**
 * The dibi driver for MySQL database
 *
 * @version $Revision$ $Date$
 */
class DibiMySqlDriver extends DibiDriver
{
    public $formats = array(
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
        self::prepare($config, 'username', 'user');
        self::prepare($config, 'password', 'pass');

        // default values
        if ($config['username'] === NULL) $config['username'] = ini_get('mysql.default_user');
        if ($config['password'] === NULL) $config['password'] = ini_get('mysql.default_password');
        if (!isset($config['host'])) {
            $config['host'] = ini_get('mysql.default_host');
            if (!isset($config['port'])) ini_get('mysql.default_port');
            if (!isset($config['host'])) $config['host'] = 'localhost';
        }

        parent::__construct($config);
    }



    protected function connect()
    {
        if (!extension_loaded('mysql')) {
            throw new DibiException("PHP extension 'mysql' is not loaded");
        }

        $config = $this->getConfig();

        if (isset($config['protocol']) && $config['protocol'] === 'unix') { // host can be socket
            $host = ':' . $config['host'];
        } else {
            $host = $config['host'] . (isset($config['port']) ? ':'.$config['port'] : '');
        }

        // some errors aren't handled. Must use $php_errormsg
        if (function_exists('ini_set')) {
            $save = ini_set('track_errors', TRUE);
        }

        $php_errormsg = '';

        if (empty($config['persistent'])) {
            $connection = @mysql_connect($host, $config['username'], $config['password'], TRUE);
        } else {
            $connection = @mysql_pconnect($host, $config['username'], $config['password']);
        }

        if (function_exists('ini_set')) {
            ini_set('track_errors', $save);
        }

        if (!is_resource($connection)) {
            $msg = mysql_error();
            if (!$msg) $msg = $php_errormsg;
            throw new DibiDatabaseException($msg, mysql_errno());
        }

        if (isset($config['charset'])) {
            @mysql_query("SET NAMES '" . $config['charset'] . "'", $connection);
            // don't handle this error...
        }

        if (isset($config['database']) && !@mysql_select_db($config['database'], $connection)) {
            throw new DibiDatabaseException(mysql_error($connection), mysql_errno($connection));
        }

        dibi::notify('connected', $this);
        return $connection;
    }



    protected function doQuery($sql)
    {
        $connection = $this->getConnection();
        $res = @mysql_query($sql, $connection);

        if ($errno = mysql_errno($connection)) {
            throw new DibiDatabaseException(mysql_error($connection), $errno, $sql);
        }

        return is_resource($res) ? new DibiMySqlResult($res) : TRUE;
    }



    public function affectedRows()
    {
        $rows = mysql_affected_rows($this->getConnection());
        return $rows < 0 ? FALSE : $rows;
    }



    public function insertId()
    {
        $id = mysql_insert_id($this->getConnection());
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
        throw new BadMethodCallException(__METHOD__ . ' is not implemented');
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

            if (in_array('auto_increment', $info['flags'])) {  // or 'primary_key' ?
                $info['type'] = dibi::FIELD_COUNTER;
            } else {
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
