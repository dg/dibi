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
class DibiOdbcDriver extends DibiDriver
{
    /**
     * Describes how convert some datatypes to SQL command
     * @var array
     */
    public $formats = array(
        'TRUE'     => "-1",
        'FALSE'    => "0",
        'date'     => "#m/d/Y#",
        'datetime' => "#m/d/Y H:i:s#",
    );

    /**
     * Affected rows
     * @var mixed
     */
    private $affectedRows = FALSE;



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

        // default values
        if (!isset($config['username'])) $config['username'] = ini_get('odbc.default_user');
        if (!isset($config['password'])) $config['password'] = ini_get('odbc.default_pw');
        if (!isset($config['dsn'])) $config['dsn'] = ini_get('odbc.default_db');

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
        if (!extension_loaded('odbc')) {
            throw new DibiException("PHP extension 'odbc' is not loaded");
        }

        $config = $this->getConfig();

        if (empty($config['persistent'])) {
            $connection = @odbc_connect($config['dsn'], $config['username'], $config['password']);
        } else {
            $connection = @odbc_pconnect($config['dsn'], $config['username'], $config['password']);
        }

        if (!is_resource($connection)) {
            throw new DibiDatabaseException(odbc_errormsg(), odbc_error());
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
        odbc_close($this->getConnection());
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
        $this->affectedRows = FALSE;
        $connection = $this->getConnection();
        $res = @odbc_exec($connection, $sql);

        if ($res === FALSE) {
            throw new DibiDatabaseException(odbc_errormsg($connection), odbc_error($connection), $sql);
        }

        if (is_resource($res)) {
            $this->affectedRows = odbc_num_rows($res);
            if ($this->affectedRows < 0) $this->affectedRows = FALSE;
            return new DibiOdbcResult($res);
        }
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    public function affectedRows()
    {
        return $this->affectedRows;
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId()
    {
        throw new BadMethodCallException(__METHOD__ . ' is not implemented');
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    public function begin()
    {
        $connection = $this->getConnection();
        if (!odbc_autocommit($connection, FALSE)) {
            throw new DibiDatabaseException(odbc_errormsg($connection), odbc_error($connection));
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
        if (!odbc_commit($connection)) {
            throw new DibiDatabaseException(odbc_errormsg($connection), odbc_error($connection));
        }
        odbc_autocommit($connection, TRUE);
        dibi::notify('commit', $this);
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        $connection = $this->getConnection();
        if (!odbc_rollback($connection)) {
            throw new DibiDatabaseException(odbc_errormsg($connection), odbc_error($connection));
        }
        odbc_autocommit($connection, TRUE);
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
            'message'  => odbc_errormsg($connection),
            'code'     => odbc_error($connection),
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
        $value = str_replace("'", "''", $value);
        return $appendQuotes
               ? "'" . $value . "'"
               : $value;
    }



    /**
     * Delimites identifier (table's or column's name, etc.)
     *
     * @param string     identifier
     * @return string    delimited identifier
     */
    public function delimite($value)
    {
        return '[' . str_replace('.', '].[', $value) . ']';
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
        // offset suppot is missing...
        if ($limit >= 0) {
           $sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ')';
        }

        if ($offset) throw new InvalidArgumentException('Offset is not implemented in driver odbc');
    }


} // class DibiOdbcDriver







/**
 * The dibi result-set class for ODBC database
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiOdbcResult extends DibiResult
{
    private $row = 0;



    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        // will return -1 with many drivers :-(
        return odbc_num_rows($this->resource);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    protected function doFetch()
    {
        return odbc_fetch_array($this->resource, $this->row++);
    }



    /**
     * Moves cursor position without fetching row
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
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
    protected function free()
    {
        odbc_free_result($this->resource);
    }



    /** this is experimental */
    protected function buildMeta()
    {
        // cache
        if ($this->meta !== NULL) {
            return $this->meta;
        }

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

        $count = odbc_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 1; $index <= $count; $index++) {
            $native = strtoupper(odbc_field_type($this->resource, $index));
            $name = odbc_field_name($this->resource, $index);
            $this->meta[$name] = array(
                'type'      => isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN,
                'native'    => $native,
                'length'    => odbc_field_len($this->resource, $index),
                'scale'     => odbc_field_scale($this->resource, $index),
                'precision' => odbc_field_precision($this->resource, $index),
            );
            $this->convert[$name] = $this->meta[$name]['type'];
        }
    }


} // class DibiOdbcResult
