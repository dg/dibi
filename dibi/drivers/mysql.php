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
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  dibi license
 * @link       http://php7.org/dibi/
 * @package    dibi
 */


/**
 * The dibi driver for MySQL database
 *
 * Connection options:
 *   - 'host' - the MySQL server host name
 *   - 'port' - the port number to attempt to connect to the MySQL server
 *   - 'socket' - the socket or named pipe
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'persistent' - try to find a persistent link?
 *   - 'database' - the database name to select
 *   - 'charset' - sets the encoding
 *   - 'unbuffered' - sends query without fetching and buffering the result rows automatically?
 *   - 'options' - driver specific constants (MYSQL_*)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiMySqlDriver extends DibiDriver
{
    /**
     * Describes how convert some datatypes to SQL command
     * @var array
     */
    public $formats = array(
        'TRUE'     => "1",
        'FALSE'    => "0",
        'date'     => "'Y-m-d'",
        'datetime' => "'Y-m-d H:i:s'",
    );


    /**
     * Creates object and (optionally) connects to a database
     *
     * @param array  connect configuration
     * @throws DibiException
     */
    public function __construct(array $config)
    {
        self::alias($config, 'username', 'user');
        self::alias($config, 'password', 'pass');
        self::alias($config, 'options');

        // default values
        if (!isset($config['username'])) $config['username'] = ini_get('mysql.default_user');
        if (!isset($config['password'])) $config['password'] = ini_get('mysql.default_password');
        if (!isset($config['host'])) {
            $host = ini_get('mysql.default_host');
            if ($host) {
                $config['host'] = $host;
                $config['port'] = ini_get('mysql.default_port');
            } else {
                if (!isset($config['socket'])) $config['socket'] = ini_get('mysql.default_socket');
                $config['host'] = NULL;
            }
        }

        parent::__construct($config);
    }



    /**
     * Connects to a database
     *
     * @throws DibiException
     * @return resource
     */
    protected function doConnect()
    {
        if (!extension_loaded('mysql')) {
            throw new DibiException("PHP extension 'mysql' is not loaded");
        }

        $config = $this->getConfig();

        if (empty($config['socket'])) {
            $host = $config['host'] . (empty($config['port']) ? '' : ':' . $config['port']);
        } else {
            $host = ':' . $config['socket'];
        }

        // some errors aren't handled. Must use $php_errormsg
        if (function_exists('ini_set')) {
            $save = ini_set('track_errors', TRUE);
        }

        $php_errormsg = '';

        if (empty($config['persistent'])) {
            $connection = @mysql_connect($host, $config['username'], $config['password'], TRUE, $config['options']);
        } else {
            $connection = @mysql_pconnect($host, $config['username'], $config['password'], $config['options']);
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

        return $connection;
    }



    /**
     * Disconnects from a database
     *
     * @return void
     */
    protected function doDisconnect()
    {
        mysql_close($this->getConnection());
    }



    /**
     * Internal: Executes the SQL query
     *
     * @param string       SQL statement.
     * @return DibiResult  Result set object
     * @throws DibiDatabaseException
     */
    protected function doQuery($sql)
    {
        $connection = $this->getConnection();

        if ($this->getConfig('unbuffered')) {
            $res = @mysql_unbuffered_query($sql, $connection);
        } else {
            $res = @mysql_query($sql, $connection);
        }

        if ($errno = mysql_errno($connection)) {
            throw new DibiDatabaseException(mysql_error($connection), $errno, $sql);
        }

        return is_resource($res) ? new DibiMySqlResult($res) : NULL;
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    public function affectedRows()
    {
        $rows = mysql_affected_rows($this->getConnection());
        return $rows < 0 ? FALSE : $rows;
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId()
    {
        $id = mysql_insert_id($this->getConnection());
        return $id < 1 ? FALSE : $id;
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    public function begin()
    {
        $this->doQuery('BEGIN');
        dibi::notify('begin', $this);
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        $this->doQuery('COMMIT');
        dibi::notify('commit', $this);
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        $this->doQuery('ROLLBACK');
        dibi::notify('rollback', $this);
    }



    /**
     * Returns last error
     *
     * @return array with items 'message' and 'code'
     */
    public function errorInfo()
    {
        $connection = $this->getConnection();
        return array(
            'message'  => mysql_error($connection),
            'code'     => mysql_errno($connection),
        );
    }



    /**
     * Escapes the string
     *
     * @param string     unescaped string
     * @param bool       quote string?
     * @return string    escaped and optionally quoted string
     */
    public function escape($value, $appendQuotes = TRUE)
    {
        $connection = $this->getConnection();
        return $appendQuotes
               ? "'" . mysql_real_escape_string($value, $connection) . "'"
               : mysql_real_escape_string($value, $connection);
    }



    /**
     * Delimites identifier (table's or column's name, etc.)
     *
     * @param string     identifier
     * @return string    delimited identifier
     */
    public function delimite($value)
    {
        return '`' . str_replace('.', '`.`', $value) . '`';
    }



    /**
     * Gets a information of the current database.
     *
     * @return DibiReflection
     */
    public function getDibiReflection()
    {
        throw new BadMethodCallException(__METHOD__ . ' is not implemented');
    }



    /**
     * Injects LIMIT/OFFSET to the SQL query
     *
     * @param string &$sql  The SQL query that will be modified.
     * @param int $limit
     * @param int $offset
     * @return void
     */
    public function applyLimit(&$sql, $limit, $offset = 0)
    {
        if ($limit < 0 && $offset < 1) return;

        // see http://dev.mysql.com/doc/refman/5.0/en/select.html
        $sql .= ' LIMIT ' . ($limit < 0 ? '18446744073709551615' : (int) $limit)
             . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
    }


}  // DibiMySqlDriver









/**
 * The dibi result-set class for MySQL database
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiMySqlResult extends DibiResult
{

    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        return mysql_num_rows($this->resource);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    protected function doFetch()
    {
        return mysql_fetch_assoc($this->resource);
    }



    /**
     * Moves cursor position without fetching row
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     */
    public function seek($row)
    {
        return mysql_data_seek($this->resource, $row);
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
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
