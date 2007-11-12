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
 * The dibi driver interacting with databases via ODBC connections
 *
 * Connection options:
 *   - 'dsn' - driver specific DSN
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'persistent' - try to find a persistent link?
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiOdbcDriver extends NObject implements DibiDriverInterface
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
     * Cursor
     * @var int
     */
    private $row = 0;



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

        // default values
        if (!isset($config['username'])) $config['username'] = ini_get('odbc.default_user');
        if (!isset($config['password'])) $config['password'] = ini_get('odbc.default_pw');
        if (!isset($config['dsn'])) $config['dsn'] = ini_get('odbc.default_db');

        if (!extension_loaded('odbc')) {
            throw new DibiException("PHP extension 'odbc' is not loaded");
        }


        if (empty($config['persistent'])) {
            $this->connection = @odbc_connect($config['dsn'], $config['username'], $config['password']);
        } else {
            $this->connection = @odbc_pconnect($config['dsn'], $config['username'], $config['password']);
        }

        if (!is_resource($this->connection)) {
            throw new DibiDatabaseException(odbc_errormsg() . ' ' . odbc_error());
        }
    }



    /**
     * Disconnects from a database
     *
     * @return void
     */
    public function disconnect()
    {
        odbc_close($this->connection);
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
        $this->resultset = @odbc_exec($this->connection, $sql);

        if ($this->resultset === FALSE) {
            throw new DibiDatabaseException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection), 0, $sql);
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
        return odbc_num_rows($this->resultset);
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId($sequence)
    {
        throw new BadMethodCallException(__METHOD__ . ' is not implemented');
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    public function begin()
    {
        if (!odbc_autocommit($this->connection, FALSE)) {
            throw new DibiDatabaseException(odbc_errormsg($this->connection)  . ' ' . odbc_error($this->connection));
        }
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        if (!odbc_commit($this->connection)) {
            throw new DibiDatabaseException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
        }
        odbc_autocommit($this->connection, TRUE);
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        if (!odbc_rollback($this->connection)) {
            throw new DibiDatabaseException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
        }
        odbc_autocommit($this->connection, TRUE);
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
        if ($type === dibi::FIELD_TEXT) return "'" . str_replace("'", "''", $value) . "'";
        if ($type === dibi::IDENTIFIER) return '[' . str_replace('.', '].[', $value) . ']';
        if ($type === dibi::FIELD_BOOL) return $value ? -1 : 0;
        if ($type === dibi::FIELD_DATE) return date("#m/d/Y#", $value);
        if ($type === dibi::FIELD_DATETIME) return date("#m/d/Y H:i:s#", $value);
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
        // offset suppot is missing...
        if ($limit >= 0) {
           $sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ')';
        }

        if ($offset) throw new InvalidArgumentException('Offset is not implemented in driver odbc');
    }




    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        // will return -1 with many drivers :-(
        return odbc_num_rows($this->resultset);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    public function fetch()
    {
        return odbc_fetch_array($this->resultset, ++$this->row);
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
        $this->row = $row;
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    public function free()
    {
        odbc_free_result($this->resultset);
    }



    /** this is experimental */
    public function buildMeta()
    {
        static $types = array(
            'CHAR'      => dibi::FIELD_TEXT,
            'COUNTER'   => dibi::FIELD_COUNTER,
            'VARCHAR'   => dibi::FIELD_TEXT,
            'LONGCHAR'  => dibi::FIELD_TEXT,
            'INTEGER'   => dibi::FIELD_INTEGER,
            'DATETIME'  => dibi::FIELD_DATETIME,
            'CURRENCY'  => dibi::FIELD_FLOAT,
            'BIT'       => dibi::FIELD_BOOL,
            'LONGBINARY'=> dibi::FIELD_BINARY,
            'SMALLINT'  => dibi::FIELD_INTEGER,
            'BYTE'      => dibi::FIELD_INTEGER,
            'BIGINT'    => dibi::FIELD_INTEGER,
            'INT'       => dibi::FIELD_INTEGER,
            'TINYINT'   => dibi::FIELD_INTEGER,
            'REAL'      => dibi::FIELD_FLOAT,
            'DOUBLE'    => dibi::FIELD_FLOAT,
            'DECIMAL'   => dibi::FIELD_FLOAT,
            'NUMERIC'   => dibi::FIELD_FLOAT,
            'MONEY'     => dibi::FIELD_FLOAT,
            'SMALLMONEY'=> dibi::FIELD_FLOAT,
            'FLOAT'     => dibi::FIELD_FLOAT,
            'YESNO'     => dibi::FIELD_BOOL,
            // and many others?
        );

        $count = odbc_num_fields($this->resultset);
        $meta = array();
        for ($index = 1; $index <= $count; $index++) {
            $native = strtoupper(odbc_field_type($this->resultset, $index));
            $name = odbc_field_name($this->resultset, $index);
            $meta[$name] = array(
                'type'      => isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN,
                'native'    => $native,
                'length'    => odbc_field_len($this->resultset, $index),
                'scale'     => odbc_field_scale($this->resultset, $index),
                'precision' => odbc_field_precision($this->resultset, $index),
            );
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
