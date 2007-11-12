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
class DibiMySqlDriver extends NObject implements DibiDriverInterface
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
        if (!extension_loaded('mysql')) {
            throw new DibiException("PHP extension 'mysql' is not loaded");
        }

        DibiConnection::alias($config, 'username', 'user');
        DibiConnection::alias($config, 'password', 'pass');
        DibiConnection::alias($config, 'options');

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


        if (empty($config['socket'])) {
            $host = $config['host'] . (empty($config['port']) ? '' : ':' . $config['port']);
        } else {
            $host = ':' . $config['socket'];
        }

        DibiDatabaseException::catchError();
        if (empty($config['persistent'])) {
            $this->connection = @mysql_connect($host, $config['username'], $config['password'], TRUE, $config['options']);
        } else {
            $this->connection = @mysql_pconnect($host, $config['username'], $config['password'], $config['options']);
        }
        DibiDatabaseException::restore();

        if (!is_resource($this->connection)) {
            throw new DibiDatabaseException(mysql_error(), mysql_errno());
        }

        if (isset($config['charset'])) {
            @mysql_query("SET NAMES '" . $config['charset'] . "'", $this->connection);
            // don't handle this error...
        }

        if (isset($config['database']) && !@mysql_select_db($config['database'], $this->connection)) {
            throw new DibiDatabaseException(mysql_error($this->connection), mysql_errno($this->connection));
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
        mysql_close($this->connection);
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
        if ($this->buffered) {
            $this->resultset = @mysql_query($sql, $this->connection);
        } else {
            $this->resultset = @mysql_unbuffered_query($sql, $this->connection);
        }

        if ($errno = mysql_errno($this->connection)) {
            throw new DibiDatabaseException(mysql_error($this->connection), $errno, $sql);
        }

        return is_resource($this->resultset);
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    public function affectedRows()
    {
        return mysql_affected_rows($this->connection);
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId($sequence)
    {
        return mysql_insert_id($this->connection);
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    public function begin()
    {
        $this->query('BEGIN');
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        $this->query('COMMIT');
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        $this->query('ROLLBACK');
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
        if ($type === dibi::FIELD_TEXT) return "'" . mysql_real_escape_string($value, $this->connection) . "'";
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
            throw new DibiDatabaseException('Row count is not available for unbuffered queries');
        }
        return mysql_num_rows($this->resultset);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    public function fetch()
    {
        return mysql_fetch_assoc($this->resultset);
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
            throw new DibiDatabaseException('Cannot seek an unbuffered result set');
        }

        if (!mysql_data_seek($this->resultset, $row)) {
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
        mysql_free_result($this->resultset);
    }



    /** this is experimental */
    public function buildMeta()
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

        $count = mysql_num_fields($this->resultset);
        $meta = array();
        for ($index = 0; $index < $count; $index++) {

            $info['native'] = $native = strtoupper(mysql_field_type($this->resultset, $index));
            $info['flags'] = explode(' ', mysql_field_flags($this->resultset, $index));
            $info['length'] = mysql_field_len($this->resultset, $index);
            $info['table'] = mysql_field_table($this->resultset, $index);

            if (in_array('auto_increment', $info['flags'])) {  // or 'primary_key' ?
                $info['type'] = dibi::FIELD_COUNTER;
            } else {
                $info['type'] = isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN;

//                if ($info['type'] === dibi::FIELD_TEXT && $info['length'] > 255)
//                    $info['type'] = dibi::FIELD_LONG_TEXT;
            }

            $name = mysql_field_name($this->resultset, $index);
            $meta[$name] = $info;
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
