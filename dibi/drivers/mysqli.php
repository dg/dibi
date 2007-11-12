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
 * The dibi driver for MySQL database via improved extension
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
 *   - 'options' - driver specific constants (MYSQLI_*)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiMySqliDriver extends DibiDriver
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
        self::alias($config, 'database');

        // default values
        if (!isset($config['username'])) $config['username'] = ini_get('mysqli.default_user');
        if (!isset($config['password'])) $config['password'] = ini_get('mysqli.default_password');
        if (!isset($config['socket'])) $config['socket'] = ini_get('mysqli.default_socket');
        if (!isset($config['host'])) {
            $config['host'] = ini_get('mysqli.default_host');
            if (!isset($config['port'])) ini_get('mysqli.default_port');
            if (!isset($config['host'])) $config['host'] = 'localhost';
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
        if (!extension_loaded('mysqli')) {
            throw new DibiException("PHP extension 'mysqli' is not loaded");
        }

        $config = $this->getConfig();

        $connection = mysqli_init();
        @mysqli_real_connect($connection, $config['host'], $config['username'], $config['password'], $config['database'], $config['port'], $config['socket'], $config['options']);

        if ($errno = mysqli_connect_errno()) {
            throw new DibiDatabaseException(mysqli_connect_error(), $errno);
        }

        if (isset($config['charset'])) {
            mysqli_query($connection, "SET NAMES '" . $config['charset'] . "'");
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
        mysqli_close($this->getConnection());
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
        $res = @mysqli_query($connection, $sql, $this->getConfig('unbuffered') ? MYSQLI_USE_RESULT :  MYSQLI_STORE_RESULT);

        if ($errno = mysqli_errno($connection)) {
            throw new DibiDatabaseException(mysqli_error($connection), $errno, $sql);
        }

        return is_object($res) ? new DibiMySqliResult($res) : NULL;
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    public function affectedRows()
    {
        $rows = mysqli_affected_rows($this->getConnection());
        return $rows < 0 ? FALSE : $rows;
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId()
    {
        $id = mysqli_insert_id($this->getConnection());
        return $id < 1 ? FALSE : $id;
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    public function begin()
    {
        $connection = $this->getConnection();
        if (!mysqli_autocommit($connection, FALSE)) {
            throw new DibiDatabaseException(mysqli_error($connection), mysqli_errno($connection));
        }
        dibi::notify('begin', $this);
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        $connection = $this->getConnection();
        if (!mysqli_commit($connection)) {
            throw new DibiDatabaseException(mysqli_error($connection), mysqli_errno($connection));
        }
        mysqli_autocommit($connection, TRUE);
        dibi::notify('commit', $this);
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        $connection = $this->getConnection();
        if (!mysqli_rollback($connection)) {
            throw new DibiDatabaseException(mysqli_error($connection), mysqli_errno($connection));
        }
        mysqli_autocommit($connection, TRUE);
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
            'message'  => mysqli_error($connection),
            'code'     => mysqli_errno($connection),
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
               ? "'" . mysqli_real_escape_string($connection, $value) . "'"
               : mysqli_real_escape_string($connection, $value);
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


} // class DibiMySqliDriver









/**
 * The dibi result-set class for MySQL database via improved extension
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiMySqliResult extends DibiResult
{

    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        return mysqli_num_rows($this->resource);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    protected function doFetch()
    {
        return mysqli_fetch_assoc($this->resource);
    }



    /**
     * Moves cursor position without fetching row
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     */
    public function seek($row)
    {
        return mysqli_data_seek($this->resource, $row);
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    protected function free()
    {
        mysqli_free_result($this->resource);
    }



    /** this is experimental */
    protected function buildMeta()
    {
        static $types = array(
            MYSQLI_TYPE_FLOAT     => dibi::FIELD_FLOAT,
            MYSQLI_TYPE_DOUBLE    => dibi::FIELD_FLOAT,
            MYSQLI_TYPE_DECIMAL   => dibi::FIELD_FLOAT,
    //      MYSQLI_TYPE_NEWDECIMAL=> dibi::FIELD_FLOAT,
    //      MYSQLI_TYPE_BIT       => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_TINY      => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_SHORT     => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_LONG      => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_LONGLONG  => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_INT24     => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_YEAR      => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_GEOMETRY  => dibi::FIELD_INTEGER,
            MYSQLI_TYPE_DATE      => dibi::FIELD_DATE,
            MYSQLI_TYPE_NEWDATE   => dibi::FIELD_DATE,
            MYSQLI_TYPE_TIMESTAMP => dibi::FIELD_DATETIME,
            MYSQLI_TYPE_TIME      => dibi::FIELD_DATETIME,
            MYSQLI_TYPE_DATETIME  => dibi::FIELD_DATETIME,
            MYSQLI_TYPE_ENUM      => dibi::FIELD_TEXT,   // eventually dibi::FIELD_INTEGER
            MYSQLI_TYPE_SET       => dibi::FIELD_TEXT,    // eventually dibi::FIELD_INTEGER
            MYSQLI_TYPE_STRING    => dibi::FIELD_TEXT,
            MYSQLI_TYPE_VAR_STRING=> dibi::FIELD_TEXT,
            MYSQLI_TYPE_TINY_BLOB => dibi::FIELD_BINARY,
            MYSQLI_TYPE_MEDIUM_BLOB=> dibi::FIELD_BINARY,
            MYSQLI_TYPE_LONG_BLOB => dibi::FIELD_BINARY,
            MYSQLI_TYPE_BLOB      => dibi::FIELD_BINARY,
        );

        $count = mysqli_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {
            $info = (array) mysqli_fetch_field_direct($this->resource, $index);
            $native = $info['native'] = $info['type'];

            if ($info['flags'] & MYSQLI_AUTO_INCREMENT_FLAG) { // or 'primary_key' ?
                $info['type'] = dibi::FIELD_COUNTER;
            } else {
                $info['type'] = isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN;
//                if ($info['type'] === dibi::FIELD_TEXT && $info['length'] > 255)
//                    $info['type'] = dibi::FIELD_LONG_TEXT;
            }

            $this->meta[$info['name']] = $info;
            $this->convert[$info['name']] = $info['type'];
        }
    }


} // class DibiMySqliResult
