<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;

use Traversable;


/**
 * dibi connection.
 *
 * @property-read int $affectedRows
 * @property-read int $insertId
 */
class Connection implements IConnection
{
	use Strict;

	/** @var array of function (Event $event); Occurs after query is executed */
	public $onEvent;

	/** @var array  Current connection configuration */
	private $config;

	/** @var Driver */
	private $driver;

	/** @var Translator */
	private $translator;

	/** @var bool  Is connected? */
	private $connected = false;

	/** @var HashMap Substitutes for identifiers */
	private $substitutes;


	/**
	 * Connection options: (see driver-specific options too)
	 *   - lazy (bool) => if true, connection will be established only when required
	 *   - result (array) => result set options
	 *       - formatDateTime => date-time format (if empty, DateTime objects will be returned)
	 *   - profiler (array or bool)
	 *       - run (bool) => enable profiler?
	 *       - file => file to log
	 *   - substitutes (array) => map of driver specific substitutes (under development)
	 * @param  mixed   connection parameters
	 * @param  string  connection name
	 * @throws Exception
	 */
	public function __construct($config, string $name = null)
	{
		if (is_string($config)) {
			parse_str($config, $config);

		} elseif ($config instanceof Traversable) {
			$tmp = [];
			foreach ($config as $key => $val) {
				$tmp[$key] = $val instanceof Traversable ? iterator_to_array($val) : $val;
			}
			$config = $tmp;

		} elseif (!is_array($config)) {
			throw new \InvalidArgumentException('Configuration must be array, string or object.');
		}

		Helpers::alias($config, 'username', 'user');
		Helpers::alias($config, 'password', 'pass');
		Helpers::alias($config, 'host', 'hostname');
		Helpers::alias($config, 'result|formatDate', 'resultDate');
		Helpers::alias($config, 'result|formatDateTime', 'resultDateTime');

		if (!isset($config['driver'])) {
			$config['driver'] = \dibi::$defaultDriver;
		}

		if ($config['driver'] instanceof Driver) {
			$this->driver = $config['driver'];
			$config['driver'] = get_class($this->driver);
		} elseif (is_subclass_of($config['driver'], Driver::class)) {
			$this->driver = new $config['driver'];
		} else {
			$class = preg_replace(['#\W#', '#sql#'], ['_', 'Sql'], ucfirst(strtolower($config['driver'])));
			$class = "Dibi\\Drivers\\{$class}Driver";
			if (!class_exists($class)) {
				throw new Exception("Unable to create instance of dibi driver '$class'.");
			}
			$this->driver = new $class;
		}

		$config['name'] = $name;
		$this->config = $config;

		// profiler
		$profilerCfg = &$config['profiler'];
		if (is_scalar($profilerCfg)) {
			$profilerCfg = ['run' => (bool) $profilerCfg];
		}
		if (!empty($profilerCfg['run'])) {
			$filter = $profilerCfg['filter'] ?? Event::QUERY;

			if (isset($profilerCfg['file'])) {
				$this->onEvent[] = [new Loggers\FileLogger($profilerCfg['file'], $filter), 'logEvent'];
			}

			if (Loggers\FirePhpLogger::isAvailable()) {
				$this->onEvent[] = [new Loggers\FirePhpLogger($filter), 'logEvent'];
			}
		}

		$this->substitutes = new HashMap(function ($expr) { return ":$expr:"; });
		if (!empty($config['substitutes'])) {
			foreach ($config['substitutes'] as $key => $value) {
				$this->substitutes->$key = $value;
			}
		}

		if (empty($config['lazy'])) {
			$this->connect();
		}
	}


	/**
	 * Automatically frees the resources allocated for this result set.
	 */
	public function __destruct()
	{
		// disconnects and rolls back transaction - do not rely on auto-disconnect and rollback!
		$this->connected && $this->driver->getResource() && $this->disconnect();
	}


	/**
	 * Connects to a database.
	 */
	final public function connect(): void
	{
		$event = $this->onEvent ? new Event($this, Event::CONNECT) : null;
		try {
			$this->driver->connect($this->config);
			$this->connected = true;
			$event && $this->onEvent($event->done());

		} catch (Exception $e) {
			$event && $this->onEvent($event->done($e));
			throw $e;
		}
	}


	/**
	 * Disconnects from a database.
	 */
	final public function disconnect(): void
	{
		$this->driver->disconnect();
		$this->connected = false;
	}


	/**
	 * Returns true when connection was established.
	 */
	final public function isConnected(): bool
	{
		return $this->connected;
	}


	/**
	 * Returns configuration variable. If no $key is passed, returns the entire array.
	 * @see self::__construct
	 * @return mixed
	 */
	final public function getConfig(string $key = null, $default = null)
	{
		return $key === null
			? $this->config
			: ($this->config[$key] ?? $default);
	}


	/**
	 * Returns the driver and connects to a database in lazy mode.
	 */
	final public function getDriver(): Driver
	{
		$this->connected || $this->connect();
		return $this->driver;
	}


	/**
	 * Generates (translates) and executes SQL query.
	 * @param  mixed      one or more arguments
	 * @return Result|int   result set or number of affected rows
	 * @throws Exception
	 */
	final public function query(...$args)
	{
		return $this->nativeQuery($this->translateArgs($args));
	}


	/**
	 * Generates SQL query.
	 * @param  mixed      one or more arguments
	 * @throws Exception
	 */
	final public function translate(...$args): string
	{
		return $this->translateArgs($args);
	}


	/**
	 * Generates and prints SQL query.
	 * @param  mixed  one or more arguments
	 */
	final public function test(...$args): bool
	{
		try {
			Helpers::dump($this->translateArgs($args));
			return true;

		} catch (Exception $e) {
			if ($e->getSql()) {
				Helpers::dump($e->getSql());
			} else {
				echo get_class($e) . ': ' . $e->getMessage() . (PHP_SAPI === 'cli' ? "\n" : '<br>');
			}
			return false;
		}
	}


	/**
	 * Generates (translates) and returns SQL query as DataSource.
	 * @param  mixed      one or more arguments
	 * @throws Exception
	 */
	final public function dataSource(...$args): DataSource
	{
		return new DataSource($this->translateArgs($args), $this);
	}


	/**
	 * Generates SQL query.
	 */
	protected function translateArgs(array $args): string
	{
		$this->connected || $this->connect();
		if (!$this->translator) {
			$this->translator = new Translator($this);
		}
		$translator = clone $this->translator;
		return $translator->translate($args);
	}


	/**
	 * Executes the SQL query.
	 * @return Result|int   result set or number of affected rows
	 * @throws Exception
	 */
	final public function nativeQuery(string $sql)
	{
		$this->connected || $this->connect();

		\dibi::$sql = $sql;
		$event = $this->onEvent ? new Event($this, Event::QUERY, $sql) : null;
		try {
			$res = $this->driver->query($sql);

		} catch (Exception $e) {
			$event && $this->onEvent($event->done($e));
			throw $e;
		}

		if ($res) {
			$res = $this->createResultSet($res);
		} else {
			$res = $this->driver->getAffectedRows();
		}

		$event && $this->onEvent($event->done($res));
		return $res;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @throws Exception
	 */
	public function getAffectedRows(): int
	{
		$this->connected || $this->connect();
		$rows = $this->driver->getAffectedRows();
		if ($rows === null || $rows < 0) {
			throw new Exception('Cannot retrieve number of affected rows.');
		}
		return $rows;
	}


	/**
	 * @deprecated
	 */
	public function affectedRows(): int
	{
		trigger_error(__METHOD__ . '() is deprecated, use getAffectedRows()', E_USER_DEPRECATED);
		return $this->getAffectedRows();
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @throws Exception
	 */
	public function getInsertId(string $sequence = null): int
	{
		$this->connected || $this->connect();
		$id = $this->driver->getInsertId($sequence);
		if ($id < 1) {
			throw new Exception('Cannot retrieve last generated ID.');
		}
		return $id;
	}


	/**
	 * @deprecated
	 */
	public function insertId(string $sequence = null): int
	{
		trigger_error(__METHOD__ . '() is deprecated, use getInsertId()', E_USER_DEPRECATED);
		return $this->getInsertId($sequence);
	}


	/**
	 * Begins a transaction (if supported).
	 */
	public function begin(string $savepoint = null): void
	{
		$this->connected || $this->connect();
		$event = $this->onEvent ? new Event($this, Event::BEGIN, $savepoint) : null;
		try {
			$this->driver->begin($savepoint);
			$event && $this->onEvent($event->done());

		} catch (Exception $e) {
			$event && $this->onEvent($event->done($e));
			throw $e;
		}
	}


	/**
	 * Commits statements in a transaction.
	 */
	public function commit(string $savepoint = null): void
	{
		$this->connected || $this->connect();
		$event = $this->onEvent ? new Event($this, Event::COMMIT, $savepoint) : null;
		try {
			$this->driver->commit($savepoint);
			$event && $this->onEvent($event->done());

		} catch (Exception $e) {
			$event && $this->onEvent($event->done($e));
			throw $e;
		}
	}


	/**
	 * Rollback changes in a transaction.
	 */
	public function rollback(string $savepoint = null): void
	{
		$this->connected || $this->connect();
		$event = $this->onEvent ? new Event($this, Event::ROLLBACK, $savepoint) : null;
		try {
			$this->driver->rollback($savepoint);
			$event && $this->onEvent($event->done());

		} catch (Exception $e) {
			$event && $this->onEvent($event->done($e));
			throw $e;
		}
	}


	/**
	 * Result set factory.
	 */
	public function createResultSet(ResultDriver $resultDriver): Result
	{
		$res = new Result($resultDriver);
		return $res->setFormat(Type::DATE, $this->config['result']['formatDate'])
			->setFormat(Type::DATETIME, $this->config['result']['formatDateTime']);
	}


	/********************* fluent SQL builders ****************d*g**/


	public function command(): Fluent
	{
		return new Fluent($this);
	}


	public function select(...$args): Fluent
	{
		return $this->command()->select(...$args);
	}


	public function update(string $table, array $args): Fluent
	{
		if (!(is_array($args) || $args instanceof Traversable)) {
			throw new \InvalidArgumentException('Arguments must be array or Traversable.');
		}
		return $this->command()->update('%n', $table)->set($args);
	}


	public function insert(string $table, array $args): Fluent
	{
		if ($args instanceof Traversable) {
			$args = iterator_to_array($args);
		} elseif (!is_array($args)) {
			throw new \InvalidArgumentException('Arguments must be array or Traversable.');
		}
		return $this->command()->insert()
			->into('%n', $table, '(%n)', array_keys($args))->values('%l', $args);
	}


	public function delete(string $table): Fluent
	{
		return $this->command()->delete()->from('%n', $table);
	}


	/********************* substitutions ****************d*g**/


	/**
	 * Returns substitution hashmap.
	 */
	public function getSubstitutes(): HashMap
	{
		return $this->substitutes;
	}


	/**
	 * Provides substitution.
	 */
	public function substitute(string $value): string
	{
		return strpos($value, ':') === false
			? $value
			: preg_replace_callback('#:([^:\s]*):#', function ($m) { return $this->substitutes->{$m[1]}; }, $value);
	}


	/********************* shortcuts ****************d*g**/


	/**
	 * Executes SQL query and fetch result - shortcut for query() & fetch().
	 * @param  mixed    one or more arguments
	 * @return Row|NULL
	 * @throws Exception
	 */
	public function fetch(...$args)
	{
		return $this->query($args)->fetch();
	}


	/**
	 * Executes SQL query and fetch results - shortcut for query() & fetchAll().
	 * @param  mixed    one or more arguments
	 * @return Row[]
	 * @throws Exception
	 */
	public function fetchAll(...$args): array
	{
		return $this->query($args)->fetchAll();
	}


	/**
	 * Executes SQL query and fetch first column - shortcut for query() & fetchSingle().
	 * @param  mixed    one or more arguments
	 * @return mixed
	 * @throws Exception
	 */
	public function fetchSingle(...$args)
	{
		return $this->query($args)->fetchSingle();
	}


	/**
	 * Executes SQL query and fetch pairs - shortcut for query() & fetchPairs().
	 * @param  mixed    one or more arguments
	 * @throws Exception
	 */
	public function fetchPairs(...$args): array
	{
		return $this->query($args)->fetchPairs();
	}


	public static function literal($value): Literal
	{
		return new Literal($value);
	}


	/********************* misc ****************d*g**/


	/**
	 * Import SQL dump from file.
	 * @param  callable $onProgress function (int $count, ?float $percent): void
	 * @return int  count of sql commands
	 */
	public function loadFile(string $file, callable $onProgress = null): int
	{
		return Helpers::loadFromFile($this, $file, $onProgress);
	}


	/**
	 * Gets a information about the current database.
	 */
	public function getDatabaseInfo(): Reflection\Database
	{
		$this->connected || $this->connect();
		return new Reflection\Database($this->driver->getReflector(), $this->config['database'] ?? null);
	}


	/**
	 * Prevents unserialization.
	 */
	public function __wakeup()
	{
		throw new NotSupportedException('You cannot serialize or unserialize ' . get_class($this) . ' instances.');
	}


	/**
	 * Prevents serialization.
	 */
	public function __sleep()
	{
		throw new NotSupportedException('You cannot serialize or unserialize ' . get_class($this) . ' instances.');
	}


	protected function onEvent($arg)
	{
		foreach ($this->onEvent ?: [] as $handler) {
			$handler($arg);
		}
	}
}
