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
 * The dibi driver for Oracle database
 *
 * Connection options:
 *   - 'database' (or 'db') - the name of the local Oracle instance or the name of the entry in tnsnames.ora
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'charset' - sets the encoding
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiOracleDriver extends DibiDriver
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

    private $autocommit = TRUE;



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
        self::alias($config, 'database', 'db');
        self::alias($config, 'charset');
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
        if (!extension_loaded('oci8')) {
            throw new DibiException("PHP extension 'oci8' is not loaded");
        }

        $config = $this->getConfig();
        $connection = @oci_new_connect($config['username'], $config['password'], $config['database'], $config['charset']);

        if (!$connection) {
            $err = oci_error();
            throw new DibiDatabaseException($err['message'], $err['code']);
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
        oci_close($this->getConnection());
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

        $statement = oci_parse($connection, $sql);
        if ($statement) {
            $res = oci_execute($statement, $this->autocommit ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT);
            $err = oci_error($statement);
            if ($err) {
                throw new DibiDatabaseException($err['message'], $err['code'], $sql);
            }
        } else {
            $err = oci_error($connection);
            throw new DibiDatabaseException($err['message'], $err['code'], $sql);
        }

        // TODO!
        return is_resource($res) ? new DibiOracleResult($statement) : TRUE;
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    public function affectedRows()
    {
        throw new BadMethodCallException(__METHOD__ . ' is not implemented');
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
        $this->autocommit = FALSE;
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        $connection = $this->getConnection();
        if (!oci_commit($connection)) {
            $err = oci_error($connection);
            throw new DibiDatabaseException($err['message'], $err['code']);
        }
        $this->autocommit = TRUE;
        dibi::notify('commit', $this);
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        $connection = $this->getConnection();
        if (!oci_rollback($connection)) {
            $err = oci_error($connection);
            throw new DibiDatabaseException($err['message'], $err['code']);
        }
        $this->autocommit = TRUE;
        dibi::notify('rollback', $this);
    }



    /**
     * Returns last error
     *
     * @return array with items 'message' and 'code'
     */
    public function errorInfo()
    {
        return oci_error($this->getConnection());
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

} // class DibiOracleDriver









/**
 * The dibi result-set class for Oracle database
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiOracleResult extends DibiResult
{

    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        return oci_num_rows($this->resource);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    protected function doFetch()
    {
        return oci_fetch_assoc($this->resource);
    }



    /**
     * Moves cursor position without fetching row
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     */
    public function seek($row)
    {
        //throw new BadMethodCallException(__METHOD__ . ' is not implemented');
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    protected function free()
    {
        oci_free_statement($this->resource);
    }



    /** this is experimental */
    protected function buildMeta()
    {
        $count = oci_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {
            $name = oci_field_name($this->resource, $index + 1);
            $this->meta[$name] = array('type' => dibi::FIELD_UNKNOWN);
            $this->convert[$name] = dibi::FIELD_UNKNOWN;
        }
    }


} // class DibiOracleResult
