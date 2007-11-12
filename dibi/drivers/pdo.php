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
 * The dibi driver for PDO
 *
 * Connection options:
 *   - 'dsn' - driver specific DSN
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'options' - driver specific options array
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiPdoDriver extends NObject implements DibiDriverInterface
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
     * @throws DibiException
     * @return void
     */
    public function connect(array &$config)
    {
        DibiConnection::alias($config, 'username', 'user');
        DibiConnection::alias($config, 'password', 'pass');
        DibiConnection::alias($config, 'dsn');
        DibiConnection::alias($config, 'options');

        if (!extension_loaded('pdo')) {
            throw new DibiException("PHP extension 'pdo' is not loaded");
        }

        $this->connection = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }


    /**
     * Disconnects from a database
     *
     * @return void
     */
    public function disconnect()
    {
        $this->connection = NULL;
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
        $this->resultset = $this->connection->query($sql);
        return $this->resultset instanceof PDOStatement;
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
        return $this->connection->lastInsertId();
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    public function begin()
    {
        $this->connection->beginTransaction();
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        $this->connection->commit();
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        $this->connection->rollBack();
    }



    /**
     * Format to SQL command
     *
     * @param string     value
     * @param string     type (dibi::FIELD_TEXT, dibi::FIELD_BOOL, dibi::FIELD_DATE, dibi::FIELD_DATETIME, dibi::IDENTIFIER)
     * @return string    formatted value
     */
    public function format($value, $type)
    {
        if ($type === dibi::FIELD_TEXT) return $this->connection->quote($value);
        if ($type === dibi::IDENTIFIER) return $value; // quoting is not supported by PDO
        if ($type === dibi::FIELD_BOOL) return $value ? 1 : 0;
        if ($type === dibi::FIELD_DATE) return date("'Y-m-d'", $value);
        if ($type === dibi::FIELD_DATETIME) return date("'Y-m-d H:i:s'", $value);
        throw new DibiException('Invalid formatting type');
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
        throw new BadMethodCallException(__METHOD__ . ' is not implemented');
    }



    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    public function rowCount()
    {
        return $this->resultset->rowCount();
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    public function fetch()
    {
        return $this->resultset->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT, $this->row++);
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
        $count = $this->resultset->columnCount();
        $meta = array();
        for ($index = 0; $index < $count; $index++) {
            $meta = $this->resultset->getColumnMeta($index);
            // TODO:
            $meta['type'] = dibi::FIELD_UNKNOWN;
            $name = $meta['name'];
            $meta[$name] =  $meta;
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
