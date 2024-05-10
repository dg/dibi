<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;

use JetBrains\PhpStorm\Language;
use Traversable;


/**
 * Dibi connection.
 *
 * @property-read int $affectedRows
 * @property-read int $insertId
 */
class Connection implements IConnection
{
	/** function (Event $event); Occurs after query is executed */
	public ?array $onEvent = [];
	private array $config;

	/** @var string[]  resultset formats */
	private array $formats;
	private ?Driver $driver = null;
	private ?Translator $translator = null;

	/** @var array<string, callable(object): Expression | null> */
	private array $translators = [];
	private bool $sortTranslators = false;
	private HashMap $substitutes;
	private int $transactionDepth = 0;


	/**
	 * Connection options: (see driver-specific options too)
	 *   - lazy (bool) => if true, connection will be established only when required
	 *   - result (array) => result set options
	 *       - normalize => normalizes result fields (default: true)
	 *       - formatDateTime => date-time format
	 *           empty for decoding as Dibi\DateTime (default)
	 *           "..." formatted according to given format, see https://www.php.net/manual/en/datetime.format.php
	 *           "native" for leaving value as is
	 *       - formatTimeInterval => time-interval format
	 *           empty for decoding as DateInterval (default)
	 *           "..." formatted according to given format, see https://www.php.net/manual/en/dateinterval.format.php
	 *           "native" for leaving value as is
	 *       - formatJson => json format
	 *           "array" for decoding json as an array (default)
	 *           "object" for decoding json as \stdClass
	 *           "native" for leaving value as is
	 *   - profiler (array)
	 *       - run (bool) => enable profiler?
	 *       - file => file to log
	 *       - errorsOnly (bool) => log only errors
	 *   - substitutes (array) => map of driver specific substitutes (under development)
	 *   - onConnect (array) => list of SQL queries to execute (by Connection::query()) after connection is established
	 * @throws Exception
	 */
	public function __construct(array $config, ?string $name = null)
	{
		Helpers::alias($config, 'username', 'user');
		Helpers::alias($config, 'password', 'pass');
		Helpers::alias($config, 'host', 'hostname');
		Helpers::alias($config, 'result|formatDate', 'resultDate');
		Helpers::alias($config, 'result|formatDateTime', 'resultDateTime');
		$config['driver'] ??= 'mysqli';
		$config['name'] = $name;
		$this->config = $config;

		$this->formats = [
			Type::Date => $this->config['result']['formatDate'],
			Type::DateTime => $this->config['result']['formatDateTime'],
			Type::JSON => $this->config['result']['formatJson'] ?? 'array',
			Type::TimeInterval => $this->config['result']['formatTimeInterval'] ?? null,
		];

		// profiler
		if (isset($config['profiler']['file']) && (!isset($config['profiler']['run']) || $config['profiler']['run'])) {
			$filter = $config['profiler']['filter'] ?? Event::QUERY;
			$errorsOnly = $config['profiler']['errorsOnly'] ?? false;
			$this->onEvent[] = [new Loggers\FileLogger($config['profiler']['file'], $filter, $errorsOnly), 'logEvent'];
		}

		$this->substitutes = new HashMap(fn(string $expr) => ":$expr:");
		if (!empty($config['substitutes'])) {
			foreach ($config['substitutes'] as $key => $value) {
				$this->substitutes->$key = $value;
			}
		}

		if (isset($config['onConnect']) && !is_array($config['onConnect'])) {
			throw new \InvalidArgumentException("Configuration option 'onConnect' must be array.");
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
		if ($this->driver && $this->driver->getResource()) {
			$this->disconnect();
		}
	}


	/**
	 * Connects to a database.
	 */
	final public function connect(): void
	{
		if ($this->config['driver'] instanceof Driver) {
			$this->driver = $this->config['driver'];
			$this->translator = new Translator($this);
			return;

		} elseif (is_subclass_of($this->config['driver'], Driver::class)) {
			$class = $this->config['driver'];

		} else {
			$class = preg_replace(['#\W#', '#sql#'], ['_', 'Sql'], ucfirst(strtolower($this->config['driver'])));
			$class = "Dibi\\Drivers\\{$class}Driver";
			if (!class_exists($class)) {
				throw new Exception("Unable to create instance of Dibi driver '$class'.");
			}
		}

		$event = $this->onEvent ? new Event($this, Event::CONNECT) : null;
		try {
			$this->driver = new $class($this->config);
			$this->translator = new Translator($this);

			if ($event) {
				$this->onEvent($event->done());
			}

			if (isset($this->config['onConnect'])) {
				foreach ($this->config['onConnect'] as $sql) {
					$this->query($sql);
				}
			}
		} catch (DriverException $e) {
			if ($event) {
				$this->onEvent($event->done($e));
			}

			throw $e;
		}
	}


	/**
	 * Disconnects from a database.
	 */
	final public function disconnect(): void
	{
		if ($this->driver) {
			$this->driver->disconnect();
			$this->driver = $this->translator = null;
		}
	}


	/**
	 * Returns true when connection was established.
	 */
	final public function isConnected(): bool
	{
		return (bool) $this->driver;
	}


	/**
	 * Returns configuration variable. If no $key is passed, returns the entire array.
	 * @see self::__construct
	 */
	final public function getConfig(?string $key = null, $default = null): mixed
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
		if (!$this->driver) {
			$this->connect();
		}

		return $this->driver;
	}


	/**
	 * Generates (translates) and executes SQL query.
	 * @throws Exception
	 */
	final public function query(#[Language('GenericSQL')] mixed ...$args): Result
	{
		return $this->nativeQuery($this->translate(...$args));
	}


	/**
	 * Generates SQL query.
	 * @throws Exception
	 */
	final public function translate(#[Language('GenericSQL')] mixed ...$args): string
	{
		if (!$this->driver) {
			$this->connect();
		}

		return (clone $this->translator)->translate($args);
	}


	/**
	 * Generates and prints SQL query.
	 */
	final public function test(#[Language('GenericSQL')] mixed ...$args): bool
	{
		try {
			Helpers::dump($this->translate(...$args));
			return true;

		} catch (Exception $e) {
			if ($e->getSql()) {
				Helpers::dump($e->getSql());
			} else {
				echo $e::class . ': ' . $e->getMessage() . (PHP_SAPI === 'cli' ? "\n" : '<br>');
			}

			return false;
		}
	}


	/**
	 * Generates (translates) and returns SQL query as DataSource.
	 * @throws Exception
	 */
	final public function dataSource(#[Language('GenericSQL')] mixed ...$args): DataSource
	{
		return new DataSource($this->translate(...$args), $this);
	}


	/**
	 * Executes the SQL query.
	 * @throws Exception
	 */
	final public function nativeQuery(#[Language('SQL')] string $sql): Result
	{
		if (!$this->driver) {
			$this->connect();
		}

		\dibi::$sql = $sql;
		$event = $this->onEvent ? new Event($this, Event::QUERY, $sql) : null;
		try {
			$res = $this->driver->query($sql);

		} catch (DriverException $e) {
			if ($event) {
				$this->onEvent($event->done($e));
			}

			throw $e;
		}

		$res = $this->createResultSet($res ?: new Drivers\NoDataResult(max(0, $this->driver->getAffectedRows())));
		if ($event) {
			$this->onEvent($event->done($res));
		}

		return $res;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @throws Exception
	 */
	public function getAffectedRows(): int
	{
		if (!$this->driver) {
			$this->connect();
		}

		$rows = $this->driver->getAffectedRows();
		if ($rows === null || $rows < 0) {
			throw new Exception('Cannot retrieve number of affected rows.');
		}

		return $rows;
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @throws Exception
	 */
	public function getInsertId(?string $sequence = null): int
	{
		if (!$this->driver) {
			$this->connect();
		}

		$id = $this->driver->getInsertId($sequence);
		if ($id === null) {
			throw new Exception('Cannot retrieve last generated ID.');
		}

		return $id;
	}


	/**
	 * Begins a transaction (if supported).
	 */
	public function begin(?string $savepoint = null): void
	{
		if ($this->transactionDepth !== 0) {
			throw new \LogicException(__METHOD__ . '() call is forbidden inside a transaction() callback');
		}

		if (!$this->driver) {
			$this->connect();
		}

		$event = $this->onEvent ? new Event($this, Event::BEGIN, $savepoint) : null;
		try {
			$this->driver->begin($savepoint);
			if ($event) {
				$this->onEvent($event->done());
			}
		} catch (DriverException $e) {
			if ($event) {
				$this->onEvent($event->done($e));
			}

			throw $e;
		}
	}


	/**
	 * Commits statements in a transaction.
	 */
	public function commit(?string $savepoint = null): void
	{
		if ($this->transactionDepth !== 0) {
			throw new \LogicException(__METHOD__ . '() call is forbidden inside a transaction() callback');
		}

		if (!$this->driver) {
			$this->connect();
		}

		$event = $this->onEvent ? new Event($this, Event::COMMIT, $savepoint) : null;
		try {
			$this->driver->commit($savepoint);
			if ($event) {
				$this->onEvent($event->done());
			}
		} catch (DriverException $e) {
			if ($event) {
				$this->onEvent($event->done($e));
			}

			throw $e;
		}
	}


	/**
	 * Rollback changes in a transaction.
	 */
	public function rollback(?string $savepoint = null): void
	{
		if ($this->transactionDepth !== 0) {
			throw new \LogicException(__METHOD__ . '() call is forbidden inside a transaction() callback');
		}

		if (!$this->driver) {
			$this->connect();
		}

		$event = $this->onEvent ? new Event($this, Event::ROLLBACK, $savepoint) : null;
		try {
			$this->driver->rollback($savepoint);
			if ($event) {
				$this->onEvent($event->done());
			}
		} catch (DriverException $e) {
			if ($event) {
				$this->onEvent($event->done($e));
			}

			throw $e;
		}
	}


	public function transaction(callable $callback): mixed
	{
		if ($this->transactionDepth === 0) {
			$this->begin();
		}

		$this->transactionDepth++;
		try {
			$res = $callback($this);
		} catch (\Throwable $e) {
			$this->transactionDepth--;
			if ($this->transactionDepth === 0) {
				$this->rollback();
			}

			throw $e;
		}

		$this->transactionDepth--;
		if ($this->transactionDepth === 0) {
			$this->commit();
		}

		return $res;
	}


	/**
	 * Result set factory.
	 */
	public function createResultSet(ResultDriver $resultDriver): Result
	{
		return (new Result($resultDriver, $this->config['result']['normalize'] ?? true))
			->setFormats($this->formats);
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


	/**
	 * @param  string|string[]  $table
	 */
	public function update($table, iterable $args): Fluent
	{
		return $this->command()->update('%n', $table)->set($args);
	}


	public function insert(string $table, iterable $args): Fluent
	{
		if ($args instanceof Traversable) {
			$args = iterator_to_array($args);
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
		return str_contains($value, ':')
			? preg_replace_callback('#:([^:\s]*):#', fn(array $m) => $this->substitutes->{$m[1]}, $value)
			: $value;
	}


	/********************* value objects translation ****************d*g**/


	/**
	 * @param  callable(object): Expression  $translator
	 */
	public function setObjectTranslator(callable $translator): void
	{
		if (!$translator instanceof \Closure) {
			$translator = \Closure::fromCallable($translator);
		}

		$param = (new \ReflectionFunction($translator))->getParameters()[0] ?? null;
		$type = $param?->getType();
		$types = match (true) {
			$type instanceof \ReflectionNamedType => [$type],
			$type instanceof \ReflectionUnionType => $type->getTypes(),
			default => throw new Exception('Object translator must have exactly one parameter with class typehint.'),
		};

		foreach ($types as $type) {
			if ($type->isBuiltin() || $type->allowsNull()) {
				throw new Exception("Object translator must have exactly one parameter with non-nullable class typehint, got '$type'.");
			}
			$this->translators[$type->getName()] = $translator;
		}
		$this->sortTranslators = true;
	}


	public function translateObject(object $object): ?Expression
	{
		if ($this->sortTranslators) {
			$this->translators = array_filter($this->translators);
			uksort($this->translators, fn($a, $b) => is_subclass_of($a, $b) ? -1 : 1);
			$this->sortTranslators = false;
		}

		if (!array_key_exists($object::class, $this->translators)) {
			$translator = null;
			foreach ($this->translators as $class => $t) {
				if ($object instanceof $class) {
					$translator = $t;
					break;
				}
			}
			$this->translators[$object::class] = $translator;
		}

		$translator = $this->translators[$object::class];
		if ($translator === null) {
			return null;
		}

		$result = $translator($object);
		if (!$result instanceof Expression) {
			throw new Exception(sprintf(
				"Object translator for class '%s' returned '%s' but %s expected.",
				$object::class,
				get_debug_type($result),
				Expression::class,
			));
		}

		return $result;
	}


	/********************* shortcuts ****************d*g**/


	/**
	 * Executes SQL query and fetch result - shortcut for query() & fetch().
	 * @throws Exception
	 */
	public function fetch(#[Language('GenericSQL')] mixed ...$args): ?Row
	{
		return $this->query($args)->fetch();
	}


	/**
	 * Executes SQL query and fetch results - shortcut for query() & fetchAll().
	 * @return Row[]|array[]
	 * @throws Exception
	 */
	public function fetchAll(#[Language('GenericSQL')] mixed ...$args): array
	{
		return $this->query($args)->fetchAll();
	}


	/**
	 * Executes SQL query and fetch first column - shortcut for query() & fetchSingle().
	 * @throws Exception
	 */
	public function fetchSingle(#[Language('GenericSQL')] mixed ...$args): mixed
	{
		return $this->query($args)->fetchSingle();
	}


	/**
	 * Executes SQL query and fetch pairs - shortcut for query() & fetchPairs().
	 * @throws Exception
	 */
	public function fetchPairs(#[Language('GenericSQL')] mixed ...$args): array
	{
		return $this->query($args)->fetchPairs();
	}


	public static function literal(string $value): Literal
	{
		return new Literal($value);
	}


	public static function expression(...$args): Expression
	{
		return new Expression(...$args);
	}


	/********************* misc ****************d*g**/


	/**
	 * Import SQL dump from file.
	 * @param  callable  $onProgress  function (int $count, ?float $percent): void
	 * @return int  count of sql commands
	 */
	public function loadFile(string $file, ?callable $onProgress = null): int
	{
		return Helpers::loadFromFile($this, $file, $onProgress);
	}


	/**
	 * Gets a information about the current database.
	 */
	public function getDatabaseInfo(): Reflection\Database
	{
		if (!$this->driver) {
			$this->connect();
		}

		return new Reflection\Database($this->driver->getReflector(), $this->config['database'] ?? null);
	}


	/**
	 * Prevents unserialization.
	 */
	public function __wakeup()
	{
		throw new NotSupportedException('You cannot serialize or unserialize ' . static::class . ' instances.');
	}


	/**
	 * Prevents serialization.
	 */
	public function __sleep()
	{
		throw new NotSupportedException('You cannot serialize or unserialize ' . static::class . ' instances.');
	}


	protected function onEvent($arg): void
	{
		foreach ($this->onEvent as $handler) {
			$handler($arg);
		}
	}
}
