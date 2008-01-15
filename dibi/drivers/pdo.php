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
 * The dibi driver for PDO
 *
 * Connection options:
 *   - 'dsn' - driver specific DSN
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'options' - driver specific options array
 *   - 'lazy' - if TRUE, connection will be established only when required
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiPdoDriver extends NObject implements IDibiDriver
{

    /**
     * Connection resource
     * @var PDO
     */
    private $connection;


    /**
     * Resultset resource
     * @var PDOStatement
     */
    private $resultset;


    /**
     * Affected rows
     * @var int
     */
    private $affectedRows = FALSE;



    /**
     * @throws DibiException
     */
    public function __construct()
    {
        if (!extension_loaded('pdo')) {
            throw new DibiDriverException("PHP extension 'pdo' is not loaded");
        }
    }



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
        DibiConnection::alias($config, 'dsn');
        DibiConnection::alias($config, 'options');

        try {
            $this->connection = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
           throw new DibiDriverException($e->getMessage(), $e->getCode());
        }

        if (!$this->connection) {
            throw new DibiDriverException('Connecting error');
        }
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
     * @param  string      SQL statement.
     * @return bool        have resultset?
     * @throws DibiDriverException
     */
    public function query($sql)
    {
        // must detect if SQL returns resultset or num of affected rows
        $cmd = strtoupper(substr(ltrim($sql), 0, 6));
        $list = array('UPDATE'=>1, 'DELETE'=>1, 'INSERT'=>1, 'REPLAC'=>1);

        if (isset($list[$cmd])) {
            $this->resultset = NULL;
            $this->affectedRows = $this->connection->exec($sql);

            if ($this->affectedRows === FALSE) {
                $this->throwException($sql);
            }

            return FALSE;

        } else {
            $this->resultset = $this->connection->query($sql);
            $this->affectedRows = FALSE;

            if ($this->resultset === FALSE) {
                $this->throwException($sql);
            }

            return TRUE;
        }
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int|FALSE  number of rows or FALSE on error
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
    public function insertId($sequence)
    {
        return $this->connection->lastInsertId();
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     * @throws DibiDriverException
     */
    public function begin()
    {
        if (!$this->connection->beginTransaction()) {
            $this->throwException();
        }
    }



    /**
     * Commits statements in a transaction.
     * @return void
     * @throws DibiDriverException
     */
    public function commit()
    {
        if (!$this->connection->commit()) {
            $this->throwException();
        }
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     * @throws DibiDriverException
     */
    public function rollback()
    {
        if (!$this->connection->rollBack()) {
            $this->throwException();
        }
    }



    /**
     * Format to SQL command
     *
     * @param  string    value
     * @param  string    type (dibi::FIELD_TEXT, dibi::FIELD_BOOL, dibi::FIELD_DATE, dibi::FIELD_DATETIME, dibi::IDENTIFIER)
     * @return string    formatted value
     * @throws InvalidArgumentException
     */
    public function format($value, $type)
    {
        if ($type === dibi::FIELD_TEXT) return $this->connection->quote($value);
        if ($type === dibi::IDENTIFIER) return $value; // quoting is not supported by PDO
        if ($type === dibi::FIELD_BOOL) return $value ? 1 : 0;
        if ($type === dibi::FIELD_DATE) return date("'Y-m-d'", $value);
        if ($type === dibi::FIELD_DATETIME) return date("'Y-m-d H:i:s'", $value);
        throw new InvalidArgumentException('Unsupported formatting type');
    }



    /**
     * Injects LIMIT/OFFSET to the SQL query
     *
     * @param  string &$sql  The SQL query that will be modified.
     * @param  int $limit
     * @param  int $offset
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
        throw new DibiDriverException('Row count is not available for unbuffered queries');
    }



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @param  bool     TRUE for associative array, FALSE for numeric
     * @return array    array on success, nonarray if no next record
     */
    public function fetch($type)
    {
        return $this->resultset->fetch($type ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
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
        throw new DibiDriverException('Cannot seek an unbuffered result set');
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



    /**
     * Returns metadata for all columns in a result set
     *
     * @return array
     * @throws DibiException
     */
    public function getColumnsMeta()
    {
        $count = $this->resultset->columnCount();
        $meta = array();
        for ($i = 0; $i < $count; $i++) {
            // items 'name' and 'table' are required
            $info = @$this->resultset->getColumnsMeta($i);
            if ($info === FALSE) {
                throw new DibiDriverException('Driver does not support meta data');
            }
            $meta[] = $info;
        }
        return $meta;
    }



    /**
     * Converts database error to DibiDriverException
     *
     * @throws DibiDriverException
     */
    protected function throwException($sql = NULL)
    {
        $err = $this->connection->errorInfo();
        throw new DibiDriverException("SQLSTATE[$err[0]]: $err[2]", $err[1], $sql);
    }



    /**
     * Returns the connection resource
     *
     * @return PDO
     */
    public function getResource()
    {
        return $this->connection;
    }



    /**
     * Returns the resultset resource
     *
     * @return PDOStatement
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
