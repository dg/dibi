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
 * The dibi driver for SQLite database
 *
 * Connection options:
 *   - 'database' (or 'file') - the filename of the SQLite database
 *   - 'persistent' - try to find a persistent link?
 *   - 'unbuffered' - sends query without fetching and buffering the result rows automatically?
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiSqliteDriver extends DibiDriver
{
    /**
     * Describes how convert some datatypes to SQL command
     * @var array
     */
    public $formats = array(
        'TRUE'     => "1",
        'FALSE'    => "0",
        'date'     => "U",
        'datetime' => "U",
    );


    /**
     * Creates object and (optionally) connects to a database
     *
     * @param array  connect configuration
     * @throws DibiException
     */
    public function __construct($config)
    {
        self::alias($config, 'database', 'file');
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
        if (!extension_loaded('sqlite')) {
            throw new DibiException("PHP extension 'sqlite' is not loaded");
        }

        $config = $this->getConfig();

        $errorMsg = '';
        if (empty($config['persistent'])) {
            $connection = @sqlite_open($config['database'], 0666, $errorMsg);
        } else {
            $connection = @sqlite_popen($config['database'], 0666, $errorMsg);
        }

        if (!$connection) {
            throw new DibiDatabaseException($errorMsg);
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
        sqlite_close($this->getConnection());
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
        $errorMsg = NULL;

        if ($this->getConfig('unbuffered')) {
            $res = @sqlite_unbuffered_query($connection, $sql, SQLITE_ASSOC, $errorMsg);
        } else {
            $res = @sqlite_query($connection, $sql, SQLITE_ASSOC, $errorMsg);
        }


        if ($errorMsg !== NULL) {
            throw new DibiDatabaseException($errorMsg, sqlite_last_error($connection), $sql);
        }

        return is_resource($res) ? new DibiSqliteResult($res) : NULL;
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    public function affectedRows()
    {
        $rows = sqlite_changes($this->getConnection());
        return $rows < 0 ? FALSE : $rows;
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId()
    {
        $id = sqlite_last_insert_rowid($this->getConnection());
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
        $code = sqlite_last_error($this->getConnection());
        return array(
            'message'  => sqlite_error_string($code),
            'code'     => $code,
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
        return $appendQuotes
               ? "'" . sqlite_escape_string($value) . "'"
               : sqlite_escape_string($value);
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
        if ($limit < 0 && $offset < 1) return;
        $sql .= ' LIMIT ' . $limit . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
    }

} // class DibiSqliteDriver









/**
 * The dibi result-set class for SQLite database
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiSqliteResult extends DibiResult
{

    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        return sqlite_num_rows($this->resource);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    protected function doFetch()
    {
        return sqlite_fetch_array($this->resource, SQLITE_ASSOC);
    }



    /**
     * Moves cursor position without fetching row
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     */
    public function seek($row)
    {
        return sqlite_seek($this->resource, $row);
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    protected function free()
    {
    }



    /** this is experimental */
    protected function buildMeta()
    {
        $count = sqlite_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {
            $name = sqlite_field_name($this->resource, $index);
            $this->meta[$name] = array('type' => dibi::FIELD_UNKNOWN);
            $this->convert[$name] = dibi::FIELD_UNKNOWN;
        }
    }


} // class DibiSqliteResult
