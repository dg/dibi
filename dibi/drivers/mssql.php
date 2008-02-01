<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com/
 *
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com/
 * @package    dibi
 */


/**
 * The dibi driver for MS SQL database.
 *
 * Connection options:
 *   - 'host' - the MS SQL server host name. It can also include a port number (hostname:port)
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'persistent' - try to find a persistent link?
 *   - 'database' - the database name to select
 *   - 'lazy' - if TRUE, connection will be established only when required
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiMsSqlDriver extends NObject implements IDibiDriver
{

    /**
     * Connection resource.
     * @var resource
     */
    private $connection;


    /**
     * Resultset resource.
     * @var resource
     */
    private $resultset;



    /**
     * @throws DibiException
     */
    public function __construct()
    {
        if (!extension_loaded('mssql')) {
            throw new DibiDriverException("PHP extension 'mssql' is not loaded.");
        }
    }



    /**
     * Connects to a database.
     *
     * @return void
     * @throws DibiException
     */
    public function connect(array &$config)
    {
        DibiConnection::alias($config, 'username', 'user');
        DibiConnection::alias($config, 'password', 'pass');
        DibiConnection::alias($config, 'host');

        if (empty($config['persistent'])) {
            $this->connection = @mssql_connect($config['host'], $config['username'], $config['password'], TRUE);
        } else {
            $this->connection = @mssql_pconnect($config['host'], $config['username'], $config['password']);
        }

        if (!is_resource($this->connection)) {
            throw new DibiDriverException("Can't connect to DB.");
        }

        if (isset($config['database']) && !@mssql_select_db($config['database'], $this->connection)) {
            throw new DibiDriverException("Can't select DB '$config[database]'.");
        }
    }



    /**
     * Disconnects from a database.
     *
     * @return void
     */
    public function disconnect()
    {
        mssql_close($this->connection);
    }



    /**
     * Executes the SQL query.
     *
     * @param  string      SQL statement.
     * @return bool        have resultset?
     * @throws DibiDriverException
     */
    public function query($sql)
    {
        $this->resultset = @mssql_query($sql, $this->connection);

        if ($this->resultset === FALSE) {
            throw new DibiDriverException('Query error', 0, $sql);
        }

        return is_resource($this->resultset);
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
     *
     * @return int|FALSE  number of rows or FALSE on error
     */
    public function affectedRows()
    {
        return mssql_rows_affected($this->connection);
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId($sequence)
    {
        throw new NotSupportedException('MS SQL does not support autoincrementing.');
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     * @throws DibiDriverException
     */
    public function begin()
    {
        $this->query('BEGIN TRANSACTION');
    }



    /**
     * Commits statements in a transaction.
     * @return void
     * @throws DibiDriverException
     */
    public function commit()
    {
        $this->query('COMMIT');
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     * @throws DibiDriverException
     */
    public function rollback()
    {
        $this->query('ROLLBACK');
    }



    /**
     * Format to SQL command.
     *
     * @param  string    value
     * @param  string    type (dibi::FIELD_TEXT, dibi::FIELD_BOOL, dibi::FIELD_DATE, dibi::FIELD_DATETIME, dibi::IDENTIFIER)
     * @return string    formatted value
     * @throws InvalidArgumentException
     */
    public function format($value, $type)
    {
        if ($type === dibi::FIELD_TEXT) return "'" . str_replace("'", "''", $value) . "'";
        if ($type === dibi::IDENTIFIER) return '[' . str_replace('.', '].[', $value) . ']';
        if ($type === dibi::FIELD_BOOL) return $value ? -1 : 0;
        if ($type === dibi::FIELD_DATE) return date("'Y-m-d'", $value);
        if ($type === dibi::FIELD_DATETIME) return date("'Y-m-d H:i:s'", $value);
        throw new InvalidArgumentException('Unsupported formatting type.');
    }



    /**
     * Injects LIMIT/OFFSET to the SQL query.
     *
     * @param  string &$sql  The SQL query that will be modified.
     * @param  int $limit
     * @param  int $offset
     * @return void
     */
    public function applyLimit(&$sql, $limit, $offset)
    {
        // offset suppot is missing...
        if ($limit >= 0) {
           $sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ')';
        }

        if ($offset) {
            throw new NotImplementedException('Offset is not implemented.');
        }
    }



    /**
     * Returns the number of rows in a result set.
     *
     * @return int
     */
    public function rowCount()
    {
        return mssql_num_rows($this->resultset);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position.
     * internal usage only
     *
     * @param  bool     TRUE for associative array, FALSE for numeric
     * @return array    array on success, nonarray if no next record
     */
    public function fetch($type)
    {
        return mssql_fetch_array($this->resultset, $type ? MSSQL_ASSOC : MSSQL_NUM);
    }



    /**
     * Moves cursor position without fetching row.
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     * @throws DibiException
     */
    public function seek($row)
    {
        return mssql_data_seek($this->resultset, $row);
    }



    /**
     * Frees the resources allocated for this result set.
     *
     * @return void
     */
    public function free()
    {
        mssql_free_result($this->resultset);
        $this->resultset = NULL;
    }



    /**
     * Returns metadata for all columns in a result set.
     *
     * @return array
     */
    public function getColumnsMeta()
    {
        $count = mssql_num_fields($this->resultset);
        $meta = array();
        for ($i = 0; $i < $count; $i++) {
            // items 'name' and 'table' are required
            $info = (array) mssql_fetch_field($this->resultset, $i);
            $info['table'] = $info['column_source'];
            $meta[] = $info;
        }
        return $meta;
    }



    /**
     * Returns the connection resource.
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->connection;
    }



    /**
     * Returns the resultset resource.
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
