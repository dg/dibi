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
class DibiMySqliDriver extends NObject implements DibiDriverInterface
{

    /**
     * Connection resource
     * @var resource
     */
    private $connection;


    /**
     * Resultset resource
     * @var resource
     */
    private $resultset;


    /**
     * Is buffered (seekable and countable)?
     * @var bool
     */
    private $buffered;


    /**
     * Connects to a database
     *
     * @return void
     * @throws DibiException
     */
    public function connect(array &$config)
    {
        DibiConnection::alias($config, 'username', 'user');
        DibiConnection::alias($config, 'password', 'pass');
        DibiConnection::alias($config, 'options');
        DibiConnection::alias($config, 'database');

        // default values
        if (!isset($config['username'])) $config['username'] = ini_get('mysqli.default_user');
        if (!isset($config['password'])) $config['password'] = ini_get('mysqli.default_password');
        if (!isset($config['socket'])) $config['socket'] = ini_get('mysqli.default_socket');
        if (!isset($config['host'])) {
            $config['host'] = ini_get('mysqli.default_host');
            if (!isset($config['port'])) ini_get('mysqli.default_port');
            if (!isset($config['host'])) $config['host'] = 'localhost';
        }

        if (!extension_loaded('mysqli')) {
            throw new DibiException("PHP extension 'mysqli' is not loaded");
        }


        $this->connection = mysqli_init();
        @mysqli_real_connect($this->connection, $config['host'], $config['username'], $config['password'], $config['database'], $config['port'], $config['socket'], $config['options']);

        if ($errno = mysqli_connect_errno()) {
            throw new DibiDatabaseException(mysqli_connect_error(), $errno);
        }

        if (isset($config['charset'])) {
            mysqli_query($this->connection, "SET NAMES '" . $config['charset'] . "'");
        }

        $this->buffered = empty($config['unbuffered']);
    }


    /**
     * Disconnects from a database
     *
     * @return void
     */
    public function disconnect()
    {
        mysqli_close($this->connection);
    }



    /**
     * Executes the SQL query
     *
     * @param string       SQL statement.
     * @return bool        have resultset?
     * @throws DibiDatabaseException
     */
    public function query($sql)
    {
        $this->resultset = @mysqli_query($this->connection, $sql, $this->buffered ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT);

        if ($errno = mysqli_errno($this->connection)) {
            throw new DibiDatabaseException(mysqli_error($this->connection), $errno, $sql);
        }

        return is_object($this->resultset);
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    public function affectedRows()
    {
        return mysqli_affected_rows($this->connection);
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId($sequence)
    {
        return mysqli_insert_id($this->connection);
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    public function begin()
    {
        if (!mysqli_autocommit($this->connection, FALSE)) {
            throw new DibiDatabaseException(mysqli_error($this->connection), mysqli_errno($this->connection));
        }
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        if (!mysqli_commit($this->connection)) {
            throw new DibiDatabaseException(mysqli_error($this->connection), mysqli_errno($this->connection));
        }
        mysqli_autocommit($this->connection, TRUE);
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        if (!mysqli_rollback($this->connection)) {
            throw new DibiDatabaseException(mysqli_error($this->connection), mysqli_errno($this->connection));
        }
        mysqli_autocommit($this->connection, TRUE);
    }



    /**
     * Format to SQL command
     *
     * @param string     value
     * @param string     type (dibi::FIELD_TEXT, dibi::FIELD_BOOL, dibi::FIELD_DATE, dibi::FIELD_DATETIME, dibi::IDENTIFIER)
     * @return string    formatted value
     * @throws InvalidArgumentException
     */
    public function format($value, $type)
    {
        if ($type === dibi::FIELD_TEXT) return "'" . mysqli_real_escape_string($this->connection, $value) . "'";
        if ($type === dibi::IDENTIFIER) return '`' . str_replace('.', '`.`', $value) . '`';
        if ($type === dibi::FIELD_BOOL) return $value ? 1 : 0;
        if ($type === dibi::FIELD_DATE) return date("'Y-m-d'", $value);
        if ($type === dibi::FIELD_DATETIME) return date("'Y-m-d H:i:s'", $value);
        throw new InvalidArgumentException('Unsupported formatting type');
    }



    /**
     * Injects LIMIT/OFFSET to the SQL query
     *
     * @param string &$sql  The SQL query that will be modified.
     * @param int $limit
     * @param int $offset
     * @return void
     */
    public function applyLimit(&$sql, $limit, $offset)
    {
        if ($limit < 0 && $offset < 1) return;

        // see http://dev.mysql.com/doc/refman/5.0/en/select.html
        $sql .= ' LIMIT ' . ($limit < 0 ? '18446744073709551615' : (int) $limit)
             . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
    }





    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        if (!$this->buffered) {
            throw new BadMethodCallException(__METHOD__ . ' is not allowed for unbuffered queries');
        }
        return mysqli_num_rows($this->resultset);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    public function fetch()
    {
        return mysqli_fetch_assoc($this->resultset);
    }



    /**
     * Moves cursor position without fetching row
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return void
     * @throws DibiException
     */
    public function seek($row)
    {
        if (!$this->buffered) {
            throw new BadMethodCallException(__METHOD__ . ' is not allowed for unbuffered queries');
        }
        if (!mysqli_data_seek($this->resultset, $row)) {
            throw new DibiDatabaseException('Unable to seek to row ' . $row);
        }
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    public function free()
    {
        mysqli_free_result($this->resultset);
    }



    /** this is experimental */
    public function buildMeta()
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

        $count = mysqli_num_fields($this->resultset);
        $meta = array();
        for ($index = 0; $index < $count; $index++) {
            $info = (array) mysqli_fetch_field_direct($this->resultset, $index);
            $native = $info['native'] = $info['type'];

            if ($info['flags'] & MYSQLI_AUTO_INCREMENT_FLAG) { // or 'primary_key' ?
                $info['type'] = dibi::FIELD_COUNTER;
            } else {
                $info['type'] = isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN;
//                if ($info['type'] === dibi::FIELD_TEXT && $info['length'] > 255)
//                    $info['type'] = dibi::FIELD_LONG_TEXT;
            }

            $meta[$info['name']] = $info;
        }
        return $meta;
    }


    /**
     * Returns the connection resource
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->connection;
    }



    /**
     * Returns the resultset resource
     *
     * @return mixed
     */
    public function getResultResource()
    {
        return $this->resultset;
    }



    /**
     * Gets a information of the current database.
     *
     * @return DibiReflection
     */
    function getDibiReflection()
    {}

}
