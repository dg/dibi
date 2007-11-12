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
 * The dibi driver for MS SQL database
 *
 * Connection options:
 *   - 'host' - the MS SQL server host name. It can also include a port number (hostname:port)
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'persistent' - try to find a persistent link?
 *   - 'database' - the database name to select
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiMsSqlDriver extends DibiDriver
{
    /**
     * Describes how convert some datatypes to SQL command
     * @var array
     */
    public $formats = array(
        'TRUE'     => "-1",
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
        self::alias($config, 'host');
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
        if (!extension_loaded('mssql')) {
            throw new DibiException("PHP extension 'mssql' is not loaded");
        }

        $config = $this->getConfig();

        if (empty($config['persistent'])) {
            $connection = @mssql_connect($config['host'], $config['username'], $config['password'], TRUE);
        } else {
            $connection = @mssql_pconnect($config['host'], $config['username'], $config['password']);
        }

        if (!is_resource($connection)) {
            throw new DibiDatabaseException("Can't connect to DB");
        }

        if (isset($config['database']) && !@mssql_select_db($config['database'], $connection)) {
            throw new DibiDatabaseException("Can't select DB '$config[database]'");
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
        mssql_close($this->getConnection());
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
        $res = @mssql_query($sql, $this->getConnection());

        if ($res === FALSE) {
            throw new DibiDatabaseException('Query error', 0, $sql);
        }

        return is_resource($res) ? new DibiMSSqlResult($res) : NULL;
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    public function affectedRows()
    {
        $rows = mssql_rows_affected($this->getConnection());
        return $rows < 0 ? FALSE : $rows;
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
        $this->doQuery('BEGIN TRANSACTION');
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
        return array(
            'message'  => NULL,
            'code'     => NULL,
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

        if ($offset) {
            throw new InvalidArgumentException('Offset is not implemented');
        }
    }


}  // DibiMsSqlDriver









/**
 * The dibi result-set class for MS SQL database
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiMSSqlResult extends DibiResult
{

    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        return mssql_num_rows($this->resource);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    protected function doFetch()
    {
        return mssql_fetch_assoc($this->resource);
    }



    /**
     * Moves cursor position without fetching row
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     */
    public function seek($row)
    {
        return mssql_data_seek($this->resource, $row);
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    protected function free()
    {
        mssql_free_result($this->resource);
    }



    /** this is experimental */
    protected function buildMeta()
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

        $count = mssql_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {

            $tmp = mssql_fetch_field($this->resource, $index);
            $type = strtoupper($tmp->type);
            $info['native'] = $tmp->type;
            $info['type'] = isset($types[$type]) ? $types[$type] : dibi::FIELD_UNKNOWN;
            $info['length'] = $tmp->max_length;
            $info['table'] = $tmp->column_source;

            $this->meta[$tmp->name] = $info;
            $this->convert[$tmp->name] = $info['type'];
        }
    }


} // class DibiMSSqlResult
