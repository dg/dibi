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
class DibiOracleDriver extends NObject implements DibiDriverInterface
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
     * @var bool
     */
    private $autocommit = TRUE;



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
        DibiConnection::alias($config, 'database', 'db');
        DibiConnection::alias($config, 'charset');

        if (!extension_loaded('oci8')) {
            throw new DibiException("PHP extension 'oci8' is not loaded");
        }

        $this->connection = @oci_new_connect($config['username'], $config['password'], $config['database'], $config['charset']);

        if (!$this->connection) {
            $err = oci_error();
            throw new DibiDatabaseException($err['message'], $err['code']);
        }
    }



    /**
     * Disconnects from a database
     *
     * @return void
     */
    public function disconnect()
    {
        oci_close($this->connection);
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

        $this->resultset = oci_parse($this->connection, $sql);
        if ($this->resultset) {
            oci_execute($this->resultset, $this->autocommit ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT);
            $err = oci_error($this->resultset);
            if ($err) {
                throw new DibiDatabaseException($err['message'], $err['code'], $sql);
            }
        } else {
            $err = oci_error($this->connection);
            throw new DibiDatabaseException($err['message'], $err['code'], $sql);
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
        throw new BadMethodCallException(__METHOD__ . ' is not implemented');
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
        $this->autocommit = FALSE;
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        if (!oci_commit($this->connection)) {
            $err = oci_error($this->connection);
            throw new DibiDatabaseException($err['message'], $err['code']);
        }
        $this->autocommit = TRUE;
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        if (!oci_rollback($this->connection)) {
            $err = oci_error($this->connection);
            throw new DibiDatabaseException($err['message'], $err['code']);
        }
        $this->autocommit = TRUE;
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
        if ($type === dibi::FIELD_TEXT) return "'" . str_replace("'", "''", $value) . "'"; // TODO: not tested
        if ($type === dibi::IDENTIFIER) return '[' . str_replace('.', '].[', $value) . ']';  // TODO: not tested
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
        return oci_num_rows($this->resultset);
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    public function fetch()
    {
        return oci_fetch_assoc($this->resultset);
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
        throw new BadMethodCallException(__METHOD__ . ' is not implemented');
    }



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    public function free()
    {
        oci_free_statement($this->resultset);
    }



    /** this is experimental */
    public function buildMeta()
    {
        $count = oci_num_fields($this->resultset);
        $meta = array();
        for ($index = 0; $index < $count; $index++) {
            $name = oci_field_name($this->resultset, $index + 1);
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
