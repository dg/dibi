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
 * dibi connection
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiConnection extends NObject
{
    /**
     * Current connection configuration
     * @var array
     */
    private $config;

    /**
     * DibiDriverInterface
     * @var array
     */
    private $driver;

    /**
     * Is connected?
     * @var bool
     */
    private $connected = FALSE;



    /**
     * Creates object and (optionally) connects to a database
     *
     * @param  array|string connection parameters
     * @throws DibiException
     */
    public function __construct($config)
    {
        // DSN string
        if (is_string($config)) {
            parse_str($config, $config);
        }

        if (!isset($config['driver'])) {
            $config['driver'] = dibi::$defaultDriver;
        }

        $class = "Dibi$config[driver]Driver";
        if (!class_exists($class)) {
            include_once __FILE__ . "/../../drivers/$config[driver].php";

            if (!class_exists($class)) {
                throw new DibiException("Unable to create instance of dibi driver class '$class'.");
            }
        }

        $this->config = $config;
        $this->driver = new $class;

        if (empty($config['lazy'])) {
            $this->connect();
        }
    }



    /**
     * Automatically frees the resources allocated for this result set
     *
     * @return void
     */
    public function __destruct()
    {
        $this->disconnect();
    }



    /**
     * Connects to a database
     *
     * @return void
     */
    final protected function connect()
    {
        $this->driver->connect($this->config);
        $this->connected = TRUE;
        dibi::notify('connected');
    }



    /**
     * Disconnects from a database
     *
     * @return void
     */
    final public function disconnect()
    {
        if ($this->connected) {
            $this->driver->disconnect();
            $this->connected = FALSE;
            dibi::notify('disconnected');
        }
    }



    /**
     * Returns configuration variable. If no $key is passed, returns the entire array.
     *
     * @see self::__construct
     * @param string
     * @param mixed  default value to use if key not found
     * @return mixed
     */
    final public function getConfig($key = NULL, $default = NULL)
    {
        if ($key === NULL) {
            return $this->config;

        } elseif (isset($this->config[$key])) {
            return $this->config[$key];

        } else {
            return $default;
        }
    }



    /**
     * Apply configuration alias or default values
     *
     * @param array  connect configuration
     * @param string key
     * @param string alias key
     * @return void
     */
    public static function alias(&$config, $key, $alias=NULL)
    {
        if (isset($config[$key])) return;

        if ($alias !== NULL && isset($config[$alias])) {
            $config[$key] = $config[$alias];
            unset($config[$alias]);
        } else {
            $config[$key] = NULL;
        }
    }



    /**
     * Returns the connection resource
     *
     * @return resource
     */
    final public function getResource()
    {
        return $this->driver->getResource();
    }



    /**
     * Generates (translates) and executes SQL query
     *
     * @param  array|mixed    one or more arguments
     * @return DibiResult     Result set object (if any)
     * @throws DibiException
     */
    final public function query($args)
    {
        if (!is_array($args)) $args = func_get_args();

        $trans = new DibiTranslator($this->driver);
        if ($trans->translate($args)) {
            return $this->nativeQuery($trans->sql);
        } else {
            throw new DibiException('SQL translate error: ' . $trans->sql);
        }
    }



    /**
     * Generates and prints SQL query
     *
     * @param  array|mixed  one or more arguments
     * @return bool
     */
    final public function test($args)
    {
        if (!is_array($args)) $args = func_get_args();

        $trans = new DibiTranslator($this->driver);
        $ok = $trans->translate($args);
        dibi::dump($trans->sql);
        return $ok;
    }



    /**
     * Executes the SQL query
     *
     * @param string          SQL statement.
     * @return DibiResult     Result set object (if any)
     * @throws DibiException
     */
    final public function nativeQuery($sql)
    {
        if (!$this->connected) $this->connect();

        dibi::notify('beforeQuery', $this, $sql);

        $res = $this->driver->query($sql);
        $res = $res ? new DibiResult(clone $this->driver) : TRUE; // backward compatibility - will be changed to NULL

        dibi::notify('afterQuery', $this, $res);

        return $res;
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query
     *
     * @return int       number of rows or FALSE on error
     */
    public function affectedRows()
    {
        $rows = $this->driver->affectedRows();
        return $rows < 0 ? FALSE : $rows;
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     *
     * @return int|FALSE  int on success or FALSE on failure
     */
    public function insertId($sequence = NULL)
    {
        $id = $this->driver->insertId($sequence);
        return $id < 1 ? FALSE : $id;
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    public function begin()
    {
        if (!$this->connected) $this->connect();
        $this->driver->begin();
        dibi::notify('begin', $this);
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        if (!$this->connected) $this->connect();
        $this->driver->commit();
        dibi::notify('commit', $this);
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        if (!$this->connected) $this->connect();
        $this->driver->rollback();
        dibi::notify('rollback', $this);
    }



    /**
     * Escapes the string
     *
     * @param string     unescaped string
     * @return string    escaped and optionally quoted string
     */
    public function escape($value)
    {
        return $this->driver->format($value, dibi::FIELD_TEXT);
    }



    /**
     * Delimites identifier (table's or column's name, etc.)
     *
     * @param string     identifier
     * @return string    delimited identifier
     */
    public function delimite($value)
    {
        return $this->driver->format($value, dibi::IDENTIFIER);
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
        $this->driver->applyLimit($sql, $limit, $offset);
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
     * Returns last error
     * @deprecated
     */
    public function errorInfo()
    {
        throw new BadMethodCallException(__METHOD__ . ' has been deprecated');
    }



}
