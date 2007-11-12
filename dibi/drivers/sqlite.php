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
class DibiSqliteDriver extends NObject implements DibiDriverInterface
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
        DibiConnection::alias($config, 'database', 'file');

        if (!extension_loaded('sqlite')) {
            throw new DibiException("PHP extension 'sqlite' is not loaded");
        }

        $errorMsg = '';
        if (empty($config['persistent'])) {
            $this->connection = @sqlite_open($config['database'], 0666, $errorMsg);
        } else {
            $this->connection = @sqlite_popen($config['database'], 0666, $errorMsg);
        }

        if (!$this->connection) {
            throw new DibiDatabaseException($errorMsg);
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
        sqlite_close($this->connection);
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
        $errorMsg = NULL;

        if ($this->buffered) {
            $this->resultset = @sqlite_query($this->connection, $sql, SQLITE_ASSOC, $errorMsg);
        } else {
            $this->resultset = @sqlite_unbuffered_query($this->connection, $sql, SQLITE_ASSOC, $errorMsg);
        }

        if ($errorMsg !== NULL) {
            throw new DibiDatabaseException($errorMsg, sqlite_last_error($this->connection), $sql);
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
        return sqlite_changes($this->connection);
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId($sequence)
    {
        return sqlite_last_insert_rowid($this->connection);
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
        if ($type === dibi::FIELD_TEXT) return "'" . sqlite_escape_string($value) . "'";
        if ($type === dibi::IDENTIFIER) return '[' . str_replace('.', '].[', $value) . ']';
        if ($type === dibi::FIELD_BOOL) return $value ? 1 : 0;
        if ($type === dibi::FIELD_DATE) return date("U", $value);
        if ($type === dibi::FIELD_DATETIME) return date("U", $value);
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
        $sql .= ' LIMIT ' . $limit . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
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
        return sqlite_num_rows($this->resultset);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    public function fetch()
    {
        return sqlite_fetch_array($this->resultset, SQLITE_ASSOC);
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
        DibiDatabaseException::catchError();
        sqlite_seek($this->resultset, $row);
        DibiDatabaseException::restore();
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    public function free()
    {
        $this->resultset = NULL;
    }



    /** this is experimental */
    public function buildMeta()
    {
        $count = sqlite_num_fields($this->resultset);
        $meta = array();
        for ($index = 0; $index < $count; $index++) {
            $name = sqlite_field_name($this->resultset, $index);
            $meta[$name] = array('type' => dibi::FIELD_UNKNOWN);
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
