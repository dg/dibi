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
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  (dibi license)
 * @category   Database
 * @package    Dibi
 * @link       http://php7.org/dibi/
 */


/**
 * The dibi driver for PostgreSql database
 *
 * @version $Revision$ $Date$
 */
final class DibiPostgreDriver extends DibiDriver
{
    /**
     * Describes how convert some datatypes to SQL command
     * @var array
     */
    public $formats = array(
        'TRUE'     => "TRUE",
        'FALSE'    => "FALSE",
        'date'     => "'Y-m-d'",
        'datetime' => "'Y-m-d H:i:s'",
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
        self::config($config, 'database', 'string');
        self::config($config, 'type');
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
        if (!extension_loaded('pgsql')) {
            throw new DibiException("PHP extension 'pgsql' is not loaded");
        }

        $config = $this->getConfig();

        // some errors aren't handled. Must use $php_errormsg
        if (function_exists('ini_set')) {
            $save = ini_set('track_errors', TRUE);
        }

        $php_errormsg = '';

        if (isset($config['persistent'])) {
            $connection = @pg_connect($config['database'], $config['type']);
        } else {
            $connection = @pg_pconnect($config['database'], $config['type']);
        }

        if (function_exists('ini_set')) {
            ini_set('track_errors', $save);
        }

        if (!is_resource($connection)) {
            throw new DibiDatabaseException($php_errormsg);
        }

        if (isset($config['charset'])) {
            @pg_set_client_encoding($connection, $config['charset']);
            // don't handle this error...
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
        pg_close($this->getConnection());
    }



    /**
     * Internal: Executes the SQL query
     *
     * @param string       SQL statement.
     * @param bool         update affected rows?
     * @return DibiResult  Result set object
     * @throws DibiDatabaseException
     */
    protected function doQuery($sql, $silent = FALSE)
    {
        $connection = $this->getConnection();
        $res = @pg_query($connection, $sql);

        if ($res === FALSE) {
            throw new DibiDatabaseException(pg_last_error($connection), 0, $sql);
        }

        if (is_resource($res)) {
            if (!$silent) {
                $this->affectedRows = pg_affected_rows($res);
                if ($this->affectedRows < 0) $this->affectedRows = FALSE;
            }
            return new DibiPostgreResult($res);
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
    public function insertId($sequence = NULL)
    {
        if ($sequence === NULL) {
            // PostgreSQL 8.1 is needed
            $res = $this->doQuery("SELECT LASTVAL() AS seq", TRUE);
        } else {
            $res = $this->doQuery("SELECT CURRVAL('$sequence') AS seq", TRUE);
        }

        if (is_resource($res)) {
            $row = pg_fetch_assoc($res);
            pg_free_result($res);
            return $row['seq'];
        }

        return FALSE;
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    public function begin()
    {
        $this->doQuery('BEGIN', TRUE);
        dibi::notify('begin', $this);
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        $this->doQuery('COMMIT', TRUE);
        dibi::notify('commit', $this);
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        $this->doQuery('ROLLBACK', TRUE);
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
            'message'  => pg_last_error($this->getConnection()),
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
        return $appendQuotes
               ? "'" . pg_escape_string($value) . "'"
               : pg_escape_string($value);
    }



    /**
     * Delimites identifier (table's or column's name, etc.)
     *
     * @param string     identifier
     * @return string    delimited identifier
     */
    public function delimite($value)
    {
        $value = str_replace('"', '""', $value);
        return '"' . str_replace('.', '"."', $value) . '"';
    }



    /**
     * Gets a information of the current database.
     *
     * @return DibiMetaData
     */
    public function getMetaData()
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
        if ($limit >= 0)
            $sql .= ' LIMIT ' . (int) $limit;

        if ($offset > 0)
            $sql .= ' OFFSET ' . (int) $offset;
    }


} // class DibiPostgreDriver









final class DibiPostgreResult extends DibiResult
{

    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        return pg_num_rows($this->resource);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    protected function doFetch()
    {
        return pg_fetch_array($this->resource, NULL, PGSQL_ASSOC);
    }



    /**
     * Moves cursor position without fetching row
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     */
    public function seek($row)
    {
        return pg_result_seek($this->resource, $row);
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    protected function free()
    {
        pg_free_result($this->resource);
    }



    /** this is experimental */
    protected function buildMeta()
    {
        static $types = array(
            'bool'      => dibi::FIELD_BOOL,
            'int2'      => dibi::FIELD_INTEGER,
            'int4'      => dibi::FIELD_INTEGER,
            'int8'      => dibi::FIELD_INTEGER,
            'numeric'   => dibi::FIELD_FLOAT,
            'float4'    => dibi::FIELD_FLOAT,
            'float8'    => dibi::FIELD_FLOAT,
            'timestamp' => dibi::FIELD_DATETIME,
            'date'      => dibi::FIELD_DATE,
            'time'      => dibi::FIELD_DATETIME,
            'varchar'   => dibi::FIELD_TEXT,
            'bpchar'    => dibi::FIELD_TEXT,
            'inet'      => dibi::FIELD_TEXT,
            'money'     => dibi::FIELD_FLOAT,
        );

        $count = pg_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {

            $info['native'] = $native = pg_field_type($this->resource, $index);
            $info['length'] = pg_field_size($this->resource, $index);
            $info['table'] = pg_field_table($this->resource, $index);
            $info['type'] = isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN;

            $name = pg_field_name($this->resource, $index);
            $this->meta[$name] = $info;
            $this->convert[$name] = $info['type'];
        }
    }


} // class DibiPostgreResult
