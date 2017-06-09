<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * dibi SQL builder via fluent interfaces. EXPERIMENTAL!
 *
 * @method Fluent select(...$field)
 * @method Fluent distinct()
 * @method Fluent from($table)
 * @method Fluent where(...$cond)
 * @method Fluent groupBy(...$field)
 * @method Fluent having(...$cond)
 * @method Fluent orderBy(...$field)
 * @method Fluent limit(int $limit)
 * @method Fluent offset(int $offset)
 * @method Fluent join(...$table)
 * @method Fluent leftJoin(...$table)
 * @method Fluent innerJoin(...$table)
 * @method Fluent rightJoin(...$table)
 * @method Fluent outerJoin(...$table)
 * @method Fluent as(...$field)
 * @method Fluent on(...$cond)
 * @method Fluent using(...$cond)
 */
class Fluent implements IDataSource
{
	use Strict;

	const REMOVE = FALSE;

	/** @var array */
	public static $masks = [
		'SELECT' => ['SELECT', 'DISTINCT', 'FROM', 'WHERE', 'GROUP BY',
			'HAVING', 'ORDER BY', 'LIMIT', 'OFFSET'],
		'UPDATE' => ['UPDATE', 'SET', 'WHERE', 'ORDER BY', 'LIMIT'],
		'INSERT' => ['INSERT', 'INTO', 'VALUES', 'SELECT'],
		'DELETE' => ['DELETE', 'FROM', 'USING', 'WHERE', 'ORDER BY', 'LIMIT'],
	];

	/** @var array  default modifiers for arrays */
	public static $modifiers = [
		'SELECT' => '%n',
		'FROM' => '%n',
		'IN' => '%in',
		'VALUES' => '%l',
		'SET' => '%a',
		'WHERE' => '%and',
		'HAVING' => '%and',
		'ORDER BY' => '%by',
		'GROUP BY' => '%by',
	];

	/** @var array  clauses separators */
	public static $separators = [
		'SELECT' => ',',
		'FROM' => ',',
		'WHERE' => 'AND',
		'GROUP BY' => ',',
		'HAVING' => 'AND',
		'ORDER BY' => ',',
		'LIMIT' => FALSE,
		'OFFSET' => FALSE,
		'SET' => ',',
		'VALUES' => ',',
		'INTO' => FALSE,
	];

	/** @var array  clauses */
	public static $clauseSwitches = [
		'JOIN' => 'FROM',
		'INNER JOIN' => 'FROM',
		'LEFT JOIN' => 'FROM',
		'RIGHT JOIN' => 'FROM',
	];

	/** @var Connection */
	private $connection;

	/** @var array */
	private $setups = [];

	/** @var string */
	private $command;

	/** @var array */
	private $clauses = [];

	/** @var array */
	private $flags = [];

	/** @var array */
	private $cursor;

	/** @var HashMap  normalized clauses */
	private static $normalizer;


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;

		if (self::$normalizer === NULL) {
			self::$normalizer = new HashMap([__CLASS__, '_formatClause']);
		}
	}


	/**
	 * Appends new argument to the clause.
	 * @param  string clause name
	 * @param  array  arguments
	 */
	public function __call(string $clause, array $args): self
	{
		$clause = self::$normalizer->$clause;

		// lazy initialization
		if ($this->command === NULL) {
			if (isset(self::$masks[$clause])) {
				$this->clauses = array_fill_keys(self::$masks[$clause], NULL);
			}
			$this->cursor = &$this->clauses[$clause];
			$this->cursor = [];
			$this->command = $clause;
		}

		// auto-switch to a clause
		if (isset(self::$clauseSwitches[$clause])) {
			$this->cursor = &$this->clauses[self::$clauseSwitches[$clause]];
		}

		if (array_key_exists($clause, $this->clauses)) {
			// append to clause
			$this->cursor = &$this->clauses[$clause];

			// TODO: really delete?
			if ($args === [self::REMOVE]) {
				$this->cursor = NULL;
				return $this;
			}

			if (isset(self::$separators[$clause])) {
				$sep = self::$separators[$clause];
				if ($sep === FALSE) { // means: replace
					$this->cursor = [];

				} elseif (!empty($this->cursor)) {
					$this->cursor[] = $sep;
				}
			}

		} else {
			// append to currect flow
			if ($args === [self::REMOVE]) {
				return $this;
			}

			$this->cursor[] = $clause;
		}

		if ($this->cursor === NULL) {
			$this->cursor = [];
		}

		// special types or argument
		if (count($args) === 1) {
			$arg = $args[0];
			// TODO: really ignore TRUE?
			if ($arg === TRUE) { // flag
				return $this;

			} elseif (is_string($arg) && preg_match('#^[a-z:_][a-z0-9_.:]*\z#i', $arg)) { // identifier
				$args = [$clause === 'AS' ? '%N' : '%n', $arg];

			} elseif (is_array($arg) || ($arg instanceof \Traversable && !$arg instanceof self)) { // any array
				if (isset(self::$modifiers[$clause])) {
					$args = [self::$modifiers[$clause], $arg];

				} elseif (is_string(key($arg))) { // associative array
					$args = ['%a', $arg];
				}
			} // case $arg === FALSE is handled above
		}

		foreach ($args as $arg) {
			if ($arg instanceof self) {
				$arg = new Literal("($arg)");
			}
			$this->cursor[] = $arg;
		}

		return $this;
	}


	/**
	 * Switch to a clause.
	 * @param  string clause name
	 */
	public function clause(string $clause): self
	{
		$this->cursor = &$this->clauses[self::$normalizer->$clause];
		if ($this->cursor === NULL) {
			$this->cursor = [];
		}

		return $this;
	}


	/**
	 * Removes a clause.
	 * @param  string clause name
	 */
	public function removeClause(string $clause): self
	{
		$this->clauses[self::$normalizer->$clause] = NULL;
		return $this;
	}


	/**
	 * Change a SQL flag.
	 * @param  string  flag name
	 */
	public function setFlag(string $flag, bool $value = TRUE): self
	{
		$flag = strtoupper($flag);
		if ($value) {
			$this->flags[$flag] = TRUE;
		} else {
			unset($this->flags[$flag]);
		}
		return $this;
	}


	/**
	 * Is a flag set?
	 * @param  string  flag name
	 */
	final public function getFlag(string $flag): bool
	{
		return isset($this->flags[strtoupper($flag)]);
	}


	/**
	 * Returns SQL command.
	 */
	final public function getCommand(): string
	{
		return $this->command;
	}


	/**
	 * Returns the dibi connection.
	 */
	final public function getConnection(): Connection
	{
		return $this->connection;
	}


	/**
	 * Adds Result setup.
	 * @param  mixed   args
	 */
	public function setupResult(string $method): self
	{
		$this->setups[] = func_get_args();
		return $this;
	}


	/********************* executing ****************d*g**/


	/**
	 * Generates and executes SQL query.
	 * @param  mixed what to return?
	 * @return Result|int  result set or number of affected rows
	 * @throws Exception
	 */
	public function execute($return = NULL)
	{
		$res = $this->query($this->_export());
		switch ($return) {
			case \dibi::IDENTIFIER:
				return $this->connection->getInsertId();
			case \dibi::AFFECTED_ROWS:
				return $this->connection->getAffectedRows();
			default:
				return $res;
		}
	}


	/**
	 * Generates, executes SQL query and fetches the single row.
	 * @return Row|NULL
	 */
	public function fetch()
	{
		if ($this->command === 'SELECT' && !$this->clauses['LIMIT']) {
			return $this->query($this->_export(NULL, ['%lmt', 1]))->fetch();
		} else {
			return $this->query($this->_export())->fetch();
		}
	}


	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed  value on success, NULL if no next record
	 */
	public function fetchSingle()
	{
		if ($this->command === 'SELECT' && !$this->clauses['LIMIT']) {
			return $this->query($this->_export(NULL, ['%lmt', 1]))->fetchSingle();
		} else {
			return $this->query($this->_export())->fetchSingle();
		}
	}


	/**
	 * Fetches all records from table.
	 */
	public function fetchAll(int $offset = NULL, int $limit = NULL): array
	{
		return $this->query($this->_export(NULL, ['%ofs %lmt', $offset, $limit]))->fetchAll();
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 * @param  string  associative descriptor
	 */
	public function fetchAssoc(string $assoc): array
	{
		return $this->query($this->_export())->fetchAssoc($assoc);
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @param  string  associative key
	 */
	public function fetchPairs(string $key = NULL, string $value = NULL): array
	{
		return $this->query($this->_export())->fetchPairs($key, $value);
	}


	/**
	 * Required by the IteratorAggregate interface.
	 */
	public function getIterator(int $offset = NULL, int $limit = NULL): ResultIterator
	{
		return $this->query($this->_export(NULL, ['%ofs %lmt', $offset, $limit]))->getIterator();
	}


	/**
	 * Generates and prints SQL query or it's part.
	 * @param  string clause name
	 */
	public function test(string $clause = NULL): bool
	{
		return $this->connection->test($this->_export($clause));
	}


	public function count(): int
	{
		return (int) $this->query([
			'SELECT COUNT(*) FROM (%ex', $this->_export(), ') [data]',
		])->fetchSingle();
	}


	private function query($args): Result
	{
		$res = $this->connection->query($args);
		foreach ($this->setups as $setup) {
			$method = array_shift($setup);
			$res->$method(...$setup);
		}
		return $res;
	}


	/********************* exporting ****************d*g**/


	public function toDataSource(): DataSource
	{
		return new DataSource($this->connection->translate($this->_export()), $this->connection);
	}


	/**
	 * Returns SQL query.
	 */
	final public function __toString(): string
	{
		try {
			return $this->connection->translate($this->_export());
		} catch (\Throwable $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);
		}
	}


	/**
	 * Generates parameters for Translator.
	 * @param  string clause name
	 */
	protected function _export(string $clause = NULL, array $args = []): array
	{
		if ($clause === NULL) {
			$data = $this->clauses;
			if ($this->command === 'SELECT' && ($data['LIMIT'] || $data['OFFSET'])) {
				$args = array_merge(['%lmt %ofs', $data['LIMIT'][0], $data['OFFSET'][0]], $args);
				unset($data['LIMIT'], $data['OFFSET']);
			}

		} else {
			$clause = self::$normalizer->$clause;
			if (array_key_exists($clause, $this->clauses)) {
				$data = [$clause => $this->clauses[$clause]];
			} else {
				return [];
			}
		}

		foreach ($data as $clause => $statement) {
			if ($statement !== NULL) {
				$args[] = $clause;
				if ($clause === $this->command && $this->flags) {
					$args[] = implode(' ', array_keys($this->flags));
				}
				foreach ($statement as $arg) {
					$args[] = $arg;
				}
			}
		}

		return $args;
	}


	/**
	 * Format camelCase clause name to UPPER CASE.
	 * @internal
	 */
	public static function _formatClause(string $s): string
	{
		if ($s === 'order' || $s === 'group') {
			$s .= 'By';
			trigger_error("Did you mean '$s'?", E_USER_NOTICE);
		}
		return strtoupper(preg_replace('#[a-z](?=[A-Z])#', '$0 ', $s));
	}


	public function __clone()
	{
		// remove references
		foreach ($this->clauses as $clause => $val) {
			$this->clauses[$clause] = &$val;
			unset($val);
		}
		$this->cursor = &$foo;
	}

}
