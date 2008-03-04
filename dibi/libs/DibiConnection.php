<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl (http://www.davidgrudl.com)
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
 * dibi connection.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiConnection extends NObject
{
    /**
     * Current connection configuration.
     * @var array
     */
    private $config;

    /**
     * IDibiDriver.
     * @var array
     */
    private $driver;

    /**
     * Is connected?
     * @var bool
     */
    private $connected = FALSE;

    /**
     * Is in transaction?
     * @var bool
     */
    private $inTxn = FALSE;



    /**
     * Creates object and (optionally) connects to a database.
     *
     * @param  array|string|IMap connection parameters
     * @throws DibiException
     */
    public function __construct($config)
    {
        // DSN string
        if (is_string($config)) {
            parse_str($config, $config);

        } elseif ($config instanceof IMap) {
            $config = $config->toArray();
        }

        if (!isset($config['driver'])) {
            $config['driver'] = dibi::$defaultDriver;
        }

        $driver = preg_replace('#[^a-z0-9_]#', '_', $config['driver']);
        $class = "Dibi" . $driver . "Driver";
        if (!class_exists($class, FALSE)) {
            include_once __FILE__ . "/../../drivers/$driver.php";

            if (!class_exists($class, FALSE)) {
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
     * Automatically frees the resources allocated for this result set.
     *
     * @return void
     */
    public function __destruct()
    {
        // disconnects and rolls back transaction - do not rely on auto-disconnect and rollback!
        $this->disconnect();
    }



    /**
     * Connects to a database.
     *
     * @return void
     */
    final protected function connect()
    {
        if (!$this->connected) {
            $this->driver->connect($this->config);
            $this->connected = TRUE;
            dibi::notify($this, 'connected');
        }
    }



    /**
     * Disconnects from a database.
     *
     * @return void
     */
    final public function disconnect()
    {
        if ($this->connected) {
            if ($this->inTxn) {
                $this->rollback();
            }
            $this->driver->disconnect();
            $this->connected = FALSE;
            dibi::notify($this, 'disconnected');
        }
    }



    /**
     * Returns configuration variable. If no $key is passed, returns the entire array.
     *
     * @see self::__construct
     * @param  string
     * @param  mixed  default value to use if key not found
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
     * Apply configuration alias or default values.
     *
     * @param  array  connect configuration
     * @param  string key
     * @param  string alias key
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
     * Returns the connection resource.
     *
     * @return resource
     */
    final public function getResource()
    {
        return $this->driver->getResource();
    }



    /**
     * Generates (translates) and executes SQL query.
     *
     * @param  array|mixed    one or more arguments
     * @return DibiResult     Result set object (if any)
     * @throws DibiException
     */
    final public function query($args)
    {
        $args = func_get_args();
        $this->connect();
        $trans = new DibiTranslator($this->driver);
        if ($trans->translate($args)) {
            return $this->nativeQuery($trans->sql);
        } else {
            throw new DibiException('SQL translate error: ' . $trans->sql);
        }
    }



    /**
     * Generates and prints SQL query.
     *
     * @param  array|mixed  one or more arguments
     * @return bool
     */
    final public function test($args)
    {
        $args = func_get_args();
        $this->connect();
        $trans = new DibiTranslator($this->driver);
        $ok = $trans->translate($args);
        dibi::dump($trans->sql);
        return $ok;
    }



    /**
     * Executes the SQL query.
     *
     * @param  string         SQL statement.
     * @return DibiResult     Result set object (if any)
     * @throws DibiException
     */
    final public function nativeQuery($sql)
    {
        $this->connect();

        dibi::$numOfQueries++;
        dibi::$sql = $sql;
        dibi::$elapsedTime = FALSE;
        $time = -microtime(TRUE);
        dibi::notify($this, 'beforeQuery', $sql);

        $res = $this->driver->query($sql) ? new DibiResult(clone $this->driver, $this->config) : TRUE; // backward compatibility - will be changed to NULL

        $time += microtime(TRUE);
        dibi::$elapsedTime = $time;
        dibi::$totalTime += $time;
        dibi::notify($this, 'afterQuery', $res);

        return $res;
    }



    /**
     * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
     *
     * @return int  number of rows
     * @throws DibiException
     */
    public function affectedRows()
    {
        $rows = $this->driver->affectedRows();
        if (!is_int($rows) || $rows < 0) throw new DibiException('Cannot retrieve number of affected rows.');
        return $rows;
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
     *
     * @param  string     optional sequence name
     * @return int
     * @throws DibiException
     */
    public function insertId($sequence = NULL)
    {
        $id = $this->driver->insertId($sequence);
        if ($id < 1) throw new DibiException('Cannot retrieve last generated ID.');
        return (int) $id;
    }



    /**
     * Begins a transaction (if supported).
     * @return void
     */
    public function begin()
    {
        $this->connect();
        if ($this->inTxn) {
    	    throw new DibiException('There is already an active transaction.');
        }
        $this->driver->begin();
        $this->inTxn = TRUE;
        dibi::notify($this, 'begin');
    }



    /**
     * Commits statements in a transaction.
     * @return void
     */
    public function commit()
    {
        if (!$this->inTxn) {
    	    throw new DibiException('There is no active transaction.');
        }
        $this->driver->commit();
        $this->inTxn = FALSE;
        dibi::notify($this, 'commit');
    }



    /**
     * Rollback changes in a transaction.
     * @return void
     */
    public function rollback()
    {
        if (!$this->inTxn) {
    	    throw new DibiException('There is no active transaction.');
        }
        $this->driver->rollback();
        $this->inTxn = FALSE;
        dibi::notify($this, 'rollback');
    }



    /**
     * Escapes the string.
     *
     * @param  string    unescaped string
     * @return string    escaped and optionally quoted string
     */
    public function escape($value)
    {
        $this->connect(); // MySQL & PDO require connection
        return $this->driver->format($value, dibi::FIELD_TEXT);
    }



    /**
     * Delimites identifier (table's or column's name, etc.).
     *
     * @param  string    identifier
     * @return string    delimited identifier
     */
    public function delimite($value)
    {
        return $this->driver->format($value, dibi::IDENTIFIER);
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
        $this->driver->applyLimit($sql, $limit, $offset);
    }



    /**
     * Import SQL dump from file - extreme fast!
     *
     * @param  string  filename
     * @return int  count of sql commands
     */
    public function loadFile($file)
    {
        $this->connect();

        @set_time_limit(0);

        $handle = @fopen($file, 'r');
        if (!$handle) {
            throw new FileNotFoundException("Cannot open file '$file'.");
        }

        $count = 0;
        $sql = '';
        while (!feof($handle)) {
            $s = fgets($handle);
            $sql .= $s;
            if (substr(rtrim($s), -1) === ';') {
                $this->driver->query($sql);
                $sql = '';
                $count++;
            }
        }
        fclose($handle);
        return $count;
    }



    /**
     * Gets a information of the current database.
     *
     * @return DibiReflection
     */
    public function getDibiReflection()
    {
        throw new NotImplementedException;
    }



    /**
     * Prevents unserialization.
     */
    public function __wakeup()
    {
        throw new NotSupportedException('You cannot serialize or unserialize ' . $this->getClass() . ' instances.');
    }



    /**
     * Prevents serialization.
     */
    public function __sleep()
    {
        throw new NotSupportedException('You cannot serialize or unserialize ' . $this->getClass() . ' instances.');
    }

}
