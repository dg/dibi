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
 * The dibi driver for PostgreSQL database
 *
 * Connection options:
 *   - 'host','hostaddr','port','dbname','user','password','connect_timeout','options','sslmode','service' - see PostgreSQL API
 *   - 'string' - or use connection string
 *   - 'persistent' - try to find a persistent link?
 *   - 'charset' - character encoding to set
 *   - 'lazy' - if TRUE, connection will be established only when required
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiPostgreDriver extends NObject implements DibiDriverInterface
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
     * Connects to a database
     *
     * @return void
     * @throws DibiException
     */
    public function connect(array &$config)
    {
        if (!extension_loaded('pgsql')) {
            throw new DibiException("PHP extension 'pgsql' is not loaded");
        }

        if (isset($config['string'])) {
            $string = $config['string'];
        } else {
            $string = '';
            foreach (array('host','hostaddr','port','dbname','user','password','connect_timeout','options','sslmode','service') as $key) {
                if (isset($config[$key])) $string .= $key . '=' . $config[$key] . ' ';
            }
        }

        DibiDriverException::catchError();
        if (isset($config['persistent'])) {
            $this->connection = @pg_connect($string, PGSQL_CONNECT_FORCE_NEW);
        } else {
            $this->connection = @pg_pconnect($string, PGSQL_CONNECT_FORCE_NEW);
        }
        DibiDriverException::restore();

        if (!is_resource($this->connection)) {
            throw new DibiDriverException('Connecting error');
        }

        if (isset($config['charset'])) {
            @pg_set_client_encoding($this->connection, $config['charset']);
            // don't handle this error...
        }
    }



    /**
     * Disconnects from a database
     *
     * @return void
     */
    public function disconnect()
    {
        pg_close($this->connection);
    }



    /**
     * Executes the SQL query
     *
     * @param string       SQL statement.
     * @param bool         update affected rows?
     * @return bool        have resultset?
     * @throws DibiDriverException
     */
    public function query($sql)
    {
        $this->resultset = @pg_query($this->connection, $sql);

        if ($this->resultset === FALSE) {
            $this->throwException($sql);
        }

        return is_resource($this->resultset);
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int|FALSE  number of rows or FALSE on error
     */
    public function affectedRows()
    {
        return pg_affected_rows($this->resultset);
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId($sequence)
    {
        if ($sequence === NULL) {
            // PostgreSQL 8.1 is needed
            $has = $this->query("SELECT LASTVAL() AS seq");
        } else {
            $has = $this->query("SELECT CURRVAL('$sequence') AS seq");
        }

        if (!$has) return FALSE;

        $row = $this->fetch();
        $this->free();
        return isset($row['seq']) ? $row['seq'] : FALSE;
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     * @throws DibiDriverException
     */
    public function begin()
    {
        $this->query('BEGIN');
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
     * Format to SQL command
     *
     * @param string     value
     * @param string     type (dibi::FIELD_TEXT, dibi::FIELD_BOOL, dibi::FIELD_DATE, dibi::FIELD_DATETIME, dibi::IDENTIFIER)
     * @return string    formatted value
     * @throws InvalidArgumentException
     */
    public function format($value, $type)
    {
        if ($type === dibi::FIELD_TEXT) return "'" . pg_escape_string($value) . "'";
        if ($type === dibi::IDENTIFIER) return '"' . str_replace('.', '"."', str_replace('"', '""', $value)) . '"';
        if ($type === dibi::FIELD_BOOL) return $value ? 'TRUE' : 'FALSE';
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
        if ($limit >= 0)
            $sql .= ' LIMIT ' . (int) $limit;

        if ($offset > 0)
            $sql .= ' OFFSET ' . (int) $offset;
    }





    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        return pg_num_rows($this->resultset);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    public function fetch()
    {
        return pg_fetch_array($this->resultset, NULL, PGSQL_ASSOC);
    }



    /**
     * Moves cursor position without fetching row
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     * @throws DibiException
     */
    public function seek($row)
    {
        return pg_result_seek($this->resultset, $row);
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    public function free()
    {
        pg_free_result($this->resultset);
        $this->resultset = NULL;
    }



    /** this is experimental */
    public function buildMeta()
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

        $count = pg_num_fields($this->resultset);
        $meta = array();
        for ($index = 0; $index < $count; $index++) {

            $info['native'] = $native = pg_field_type($this->resultset, $index);
            $info['length'] = pg_field_size($this->resultset, $index);
            $info['table'] = pg_field_table($this->resultset, $index);
            $info['type'] = isset($types[$native]) ? $types[$native] : dibi::FIELD_UNKNOWN;

            $name = pg_field_name($this->resultset, $index);
            $meta[$name] = $info;
        }
        return $meta;
    }



    /**
     * Converts database error to DibiDriverException
     *
     * @throws DibiDriverException
     */
    protected function throwException($sql=NULL)
    {
        throw new DibiDriverException(pg_last_error($this->connection), 0, $sql);
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
