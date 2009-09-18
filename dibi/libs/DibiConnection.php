<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com
 *
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */



/**
 * dibi connection.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiConnection extends DibiObject
{
	/** @var array  Current connection configuration */
	private $config;

	/** @var IDibiDriver  Driver */
	private $driver;

	/** @var IDibiProfiler  Profiler */
	private $profiler;

	/** @var bool  Is connected? */
	private $connected = FALSE;

	/** @var bool  Is in transaction? */
	private $inTxn = FALSE;



	/**
	 * Creates object and (optionally) connects to a database.
	 * @param  array|string|ArrayObject connection parameters
	 * @param  string       connection name
	 * @throws DibiException
	 */
	public function __construct($config, $name = NULL)
	{
		if (class_exists(/*Nette\*/'Debug', FALSE)) {
			/*Nette\*/Debug::addColophon(array('dibi', 'getColophon'));
		}

		// DSN string
		if (is_string($config)) {
			parse_str($config, $config);

		} elseif ($config instanceof ArrayObject) {
			$config = (array) $config;

		} elseif (!is_array($config)) {
			throw new InvalidArgumentException('Configuration must be array, string or ArrayObject.');
		}

		self::alias($config, 'username', 'user');
		self::alias($config, 'password', 'pass');
		self::alias($config, 'host', 'hostname');

		if (!isset($config['driver'])) {
			$config['driver'] = dibi::$defaultDriver;
		}

		$driver = preg_replace('#[^a-z0-9_]#', '_', $config['driver']);
		$class = "Dibi" . $driver . "Driver";
		if (!class_exists($class, FALSE)) {
			include_once dirname(__FILE__) . "/../drivers/$driver.php";

			if (!class_exists($class, FALSE)) {
				throw new DibiException("Unable to create instance of dibi driver '$class'.");
			}
		}

		$config['name'] = $name;
		$this->config = $config;
		$this->driver = new $class;

		if (!empty($config['profiler'])) {
			$class = $config['profiler'];
			if (is_numeric($class) || is_bool($class)) {
				$class = 'DibiProfiler';
			}
			if (!class_exists($class)) {
				throw new DibiException("Unable to create instance of dibi profiler '$class'.");
			}
			$this->setProfiler(new $class);
		}

		if (!empty($config['substitutes'])) {
			foreach ($config['substitutes'] as $key => $value) {
				dibi::addSubst($key, $value);
			}
		}

		if (empty($config['lazy'])) {
			$this->connect();
		}
	}



	/**
	 * Automatically frees the resources allocated for this result set.
	 * @return void
	 */
	public function __destruct()
	{
		// disconnects and rolls back transaction - do not rely on auto-disconnect and rollback!
		$this->disconnect();
	}



	/**
	 * Connects to a database.
	 * @return void
	 */
	final protected function connect()
	{
		if (!$this->connected) {
			if ($this->profiler !== NULL) {
				$ticket = $this->profiler->before($this, IDibiProfiler::CONNECT);
			}
			$this->driver->connect($this->config);
			$this->connected = TRUE;
			if (isset($ticket)) {
				$this->profiler->after($ticket);
			}
		}
	}



	/**
	 * Disconnects from a database.
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
		}
	}



	/**
	 * Returns TRUE when connection was established.
	 * @return bool
	 */
	final public function isConnected()
	{
		return $this->connected;
	}



	/**
	 * Returns configuration variable. If no $key is passed, returns the entire array.
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
	 * @return IDibiDriver
	 */
	final public function getDriver()
	{
		return $this->driver;
	}



	/**
	 * Returns the connection resource.
	 * @return resource
	 * @deprecated use getDriver()->getResource()
	 */
	final public function getResource()
	{
		trigger_error('Deprecated: use getDriver()->getResource(...) instead.', E_USER_WARNING);
		return $this->driver->getResource();
	}



	/**
	 * Generates (translates) and executes SQL query.
	 * @param  array|mixed      one or more arguments
	 * @return DibiResult|int   result set object (if any)
	 * @throws DibiException
	 */
	final public function query($args)
	{
		$args = func_get_args();
		$this->connect();
		$translator = new DibiTranslator($this->driver);
		return $this->nativeQuery($translator->translate($args));
	}



	/**
	 * Generates and returns SQL query.
	 * @param  array|mixed      one or more arguments
	 * @return string
	 * @throws DibiException
	 */
	final public function sql($args)
	{
		$args = func_get_args();
		$this->connect();
		$translator = new DibiTranslator($this->driver);
		return $translator->translate($args);
	}



	/**
	 * Generates and prints SQL query.
	 * @param  array|mixed  one or more arguments
	 * @return bool
	 */
	final public function test($args)
	{
		$args = func_get_args();
		$this->connect();
		try {
			$translator = new DibiTranslator($this->driver);
			dibi::dump($translator->translate($args));
			return TRUE;

		} catch (DibiException $e) {
			dibi::dump($e->getSql());
			return FALSE;
		}
	}



	/**
	 * Generates (translates) and returns SQL query as DibiDataSource.
	 * @param  array|mixed      one or more arguments
	 * @return DibiDataSource
	 * @throws DibiException
	 */
	final public function dataSource($args)
	{
		$args = func_get_args();
		$this->connect();
		$translator = new DibiTranslator($this->driver);
		return new DibiDataSource($translator->translate($args), $this);
	}



	/**
	 * Executes the SQL query.
	 * @param  string           SQL statement.
	 * @return DibiResult|int   result set object (if any)
	 * @throws DibiException
	 */
	final public function nativeQuery($sql)
	{
		$this->connect();

		if ($this->profiler !== NULL) {
			$event = IDibiProfiler::QUERY;
			if (preg_match('#\s*(SELECT|UPDATE|INSERT|DELETE)#i', $sql, $matches)) {
				static $events = array(
					'SELECT' => IDibiProfiler::SELECT, 'UPDATE' => IDibiProfiler::UPDATE,
					'INSERT' => IDibiProfiler::INSERT, 'DELETE' => IDibiProfiler::DELETE,
				);
				$event = $events[strtoupper($matches[1])];
			}
			$ticket = $this->profiler->before($this, $event, $sql);
		}
		// TODO: move to profiler?
		dibi::$numOfQueries++;
		dibi::$sql = $sql;
		dibi::$elapsedTime = FALSE;
		$time = -microtime(TRUE);

		if ($res = $this->driver->query($sql)) { // intentionally =
			$res = new DibiResult($res, $this->config);
		} else {
			$res = $this->driver->getAffectedRows();
		}

		$time += microtime(TRUE);
		dibi::$elapsedTime = $time;
		dibi::$totalTime += $time;

		if (isset($ticket)) {
			$this->profiler->after($ticket, $res);
		}
		return $res;
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int  number of rows
	 * @throws DibiException
	 */
	public function getAffectedRows()
	{
		$rows = $this->driver->getAffectedRows();
		if (!is_int($rows) || $rows < 0) throw new DibiException('Cannot retrieve number of affected rows.');
		return $rows;
	}



	/**
	 * Gets the number of affected rows. Alias for getAffectedRows().
	 * @return int  number of rows
	 * @throws DibiException
	 */
	public function affectedRows()
	{
		return $this->getAffectedRows();
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @param  string     optional sequence name
	 * @return int
	 * @throws DibiException
	 */
	public function getInsertId($sequence = NULL)
	{
		$id = $this->driver->getInsertId($sequence);
		if ($id < 1) throw new DibiException('Cannot retrieve last generated ID.');
		return (int) $id;
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column. Alias for getInsertId().
	 * @param  string     optional sequence name
	 * @return int
	 * @throws DibiException
	 */
	public function insertId($sequence = NULL)
	{
		return $this->getInsertId($sequence);
	}



	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 */
	public function begin($savepoint = NULL)
	{
		$this->connect();
		if (!$savepoint && $this->inTxn) {
			throw new DibiException('There is already an active transaction.');
		}
		if ($this->profiler !== NULL) {
			$ticket = $this->profiler->before($this, IDibiProfiler::BEGIN, $savepoint);
		}
		if ($savepoint && !$this->inTxn) {
			$this->driver->begin();
		}
		$this->driver->begin($savepoint);

		$this->inTxn = TRUE;
		if (isset($ticket)) {
			$this->profiler->after($ticket);
		}
	}



	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 */
	public function commit($savepoint = NULL)
	{
		if (!$this->inTxn) {
			throw new DibiException('There is no active transaction.');
		}
		if ($this->profiler !== NULL) {
			$ticket = $this->profiler->before($this, IDibiProfiler::COMMIT, $savepoint);
		}
		$this->driver->commit($savepoint);
		$this->inTxn = (bool) $savepoint;
		if (isset($ticket)) {
			$this->profiler->after($ticket);
		}
	}



	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 */
	public function rollback($savepoint = NULL)
	{
		if (!$this->inTxn) {
			throw new DibiException('There is no active transaction.');
		}
		if ($this->profiler !== NULL) {
			$ticket = $this->profiler->before($this, IDibiProfiler::ROLLBACK, $savepoint);
		}
		$this->driver->rollback($savepoint);
		$this->inTxn = (bool) $savepoint;
		if (isset($ticket)) {
			$this->profiler->after($ticket);
		}
	}



	/**
	 * Is in transaction?
	 * @return bool
	 */
	public function inTransaction()
	{
		return $this->inTxn;
	}



	/**
	 * Encodes data for use in a SQL statement.
	 * @param  string    unescaped string
	 * @param  string    type (dibi::TEXT, dibi::BOOL, ...)
	 * @return string    escaped and quoted string
	 * @deprecated
	 */
	public function escape($value, $type = dibi::TEXT)
	{
		trigger_error('Deprecated: use getDriver()->escape(...) instead.', E_USER_WARNING);
		$this->connect(); // MySQL & PDO require connection
		return $this->driver->escape($value, $type);
	}



	/**
	 * Decodes data from result set.
	 * @param  string    value
	 * @param  string    type (dibi::BINARY)
	 * @return string    decoded value
	 * @deprecated
	 */
	public function unescape($value, $type = dibi::BINARY)
	{
		trigger_error('Deprecated: use getDriver()->unescape(...) instead.', E_USER_WARNING);
		return $this->driver->unescape($value, $type);
	}



	/**
	 * Delimites identifier (table's or column's name, etc.).
	 * @param  string    identifier
	 * @return string    delimited identifier
	 * @deprecated
	 */
	public function delimite($value)
	{
		trigger_error('Deprecated: use getDriver()->escape(...) instead.', E_USER_WARNING);
		return $this->driver->escape($value, dibi::IDENTIFIER);
	}



	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 * @param  string &$sql  The SQL query that will be modified.
	 * @param  int $limit
	 * @param  int $offset
	 * @return void
	 * @deprecated
	 */
	public function applyLimit(&$sql, $limit, $offset)
	{
		trigger_error('Deprecated: use getDriver()->applyLimit(...) instead.', E_USER_WARNING);
		$this->driver->applyLimit($sql, $limit, $offset);
	}



	/********************* fluent SQL builders ****************d*g**/



	/**
	 * @return DibiFluent
	 */
	public function command()
	{
		return new DibiFluent($this);
	}



	/**
	 * @param  string    column name
	 * @return DibiFluent
	 */
	public function select($args)
	{
		$args = func_get_args();
		return $this->command()->__call('select', $args);
	}



	/**
	 * @param  string   table
	 * @param  array
	 * @return DibiFluent
	 */
	public function update($table, $args)
	{
		if (!(is_array($args) || $args instanceof ArrayObject)) {
			throw new InvalidArgumentException('Arguments must be array or ArrayObject.');
		}
		return $this->command()->update('%n', $table)->set($args);
	}



	/**
	 * @param  string   table
	 * @param  array
	 * @return DibiFluent
	 */
	public function insert($table, $args)
	{
		if ($args instanceof ArrayObject) {
			$args = (array) $args;
		} elseif (!is_array($args)) {
			throw new InvalidArgumentException('Arguments must be array or ArrayObject.');
		}
		return $this->command()->insert()
			->into('%n', $table, '(%n)', array_keys($args))->values('%l', $args);
	}



	/**
	 * @param  string   table
	 * @return DibiFluent
	 */
	public function delete($table)
	{
		return $this->command()->delete()->from('%n', $table);
	}



	/********************* profiler ****************d*g**/



	/**
	 * @param  IDibiProfiler
	 * @return DibiConnection  provides a fluent interface
	 */
	public function setProfiler(IDibiProfiler $profiler = NULL)
	{
		$this->profiler = $profiler;
		return $this;
	}



	/**
	 * @return IDibiProfiler
	 */
	public function getProfiler()
	{
		return $this->profiler;
	}



	/********************* shortcuts ****************d*g**/



	/**
	 * Executes SQL query and fetch result - shortcut for query() & fetch().
	 * @param  array|mixed    one or more arguments
	 * @return DibiRow
	 * @throws DibiException
	 */
	public function fetch($args)
	{
		$args = func_get_args();
		return $this->query($args)->fetch();
	}



	/**
	 * Executes SQL query and fetch results - shortcut for query() & fetchAll().
	 * @param  array|mixed    one or more arguments
	 * @return array of DibiRow
	 * @throws DibiException
	 */
	public function fetchAll($args)
	{
		$args = func_get_args();
		return $this->query($args)->fetchAll();
	}



	/**
	 * Executes SQL query and fetch first column - shortcut for query() & fetchSingle().
	 * @param  array|mixed    one or more arguments
	 * @return string
	 * @throws DibiException
	 */
	public function fetchSingle($args)
	{
		$args = func_get_args();
		return $this->query($args)->fetchSingle();
	}



	/**
	 * Executes SQL query and fetch pairs - shortcut for query() & fetchPairs().
	 * @param  array|mixed    one or more arguments
	 * @return string
	 * @throws DibiException
	 */
	public function fetchPairs($args)
	{
		$args = func_get_args();
		return $this->query($args)->fetchPairs();
	}



	/********************* misc ****************d*g**/



	/**
	 * Import SQL dump from file - extreme fast!
	 * @param  string  filename
	 * @return int  count of sql commands
	 */
	public function loadFile($file)
	{
		$this->connect();

		@set_time_limit(0); // intentionally @

		$handle = @fopen($file, 'r'); // intentionally @
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
	 * Gets a information about the current database.
	 * @return DibiDatabaseInfo
	 */
	public function getDatabaseInfo()
	{
		$this->connect();
		return new DibiDatabaseInfo($this->driver, isset($this->config['database']) ? $this->config['database'] : NULL);
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
