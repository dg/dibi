<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * Default implementation of IDataSource for dibi.
 *
 */
class DataSource implements IDataSource
{
	use Strict;

	/** @var Connection */
	private $connection;

	/** @var string */
	private $sql;

	/** @var Result */
	private $result;

	/** @var int */
	private $count;

	/** @var int */
	private $totalCount;

	/** @var array */
	private $cols = [];

	/** @var array */
	private $sorting = [];

	/** @var array */
	private $conds = [];

	/** @var int|NULL */
	private $offset;

	/** @var int|NULL */
	private $limit;


	/**
	 * @param  string  SQL command or table or view name, as data source
	 */
	public function __construct(string $sql, Connection $connection)
	{
		if (strpbrk($sql, " \t\r\n") === FALSE) {
			$this->sql = $connection->getDriver()->escapeIdentifier($sql); // table name
		} else {
			$this->sql = '(' . $sql . ') t'; // SQL command
		}
		$this->connection = $connection;
	}


	/**
	 * Selects columns to query.
	 * @param  string|array  column name or array of column names
	 * @param  string        column alias
	 */
	public function select($col, string $as = NULL): self
	{
		if (is_array($col)) {
			$this->cols = $col;
		} else {
			$this->cols[$col] = $as;
		}
		$this->result = NULL;
		return $this;
	}


	/**
	 * Adds conditions to query.
	 * @param  mixed  conditions
	 */
	public function where($cond): self
	{
		if (is_array($cond)) {
			// TODO: not consistent with select and orderBy
			$this->conds[] = $cond;
		} else {
			$this->conds[] = func_get_args();
		}
		$this->result = $this->count = NULL;
		return $this;
	}


	/**
	 * Selects columns to order by.
	 * @param  string|array  column name or array of column names
	 * @param  string        sorting direction
	 */
	public function orderBy($row, string $sorting = 'ASC'): self
	{
		if (is_array($row)) {
			$this->sorting = $row;
		} else {
			$this->sorting[$row] = $sorting;
		}
		$this->result = NULL;
		return $this;
	}


	/**
	 * Limits number of rows.
	 */
	public function applyLimit(int $limit, int $offset = NULL): self
	{
		$this->limit = $limit;
		$this->offset = $offset;
		$this->result = $this->count = NULL;
		return $this;
	}


	/**
	 * Returns the dibi connection.
	 */
	final public function getConnection(): Connection
	{
		return $this->connection;
	}


	/********************* executing ****************d*g**/


	/**
	 * Returns (and queries) Result.
	 */
	public function getResult(): Result
	{
		if ($this->result === NULL) {
			$this->result = $this->connection->nativeQuery($this->__toString());
		}
		return $this->result;
	}


	public function getIterator(): ResultIterator
	{
		return $this->getResult()->getIterator();
	}


	/**
	 * Generates, executes SQL query and fetches the single row.
	 * @return Row|NULL
	 */
	public function fetch()
	{
		return $this->getResult()->fetch();
	}


	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed  value on success, NULL if no next record
	 */
	public function fetchSingle()
	{
		return $this->getResult()->fetchSingle();
	}


	/**
	 * Fetches all records from table.
	 */
	public function fetchAll(): array
	{
		return $this->getResult()->fetchAll();
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 * @param  string  associative descriptor
	 */
	public function fetchAssoc(string $assoc): array
	{
		return $this->getResult()->fetchAssoc($assoc);
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @param  string  associative key
	 */
	public function fetchPairs(string $key = NULL, string $value = NULL): array
	{
		return $this->getResult()->fetchPairs($key, $value);
	}


	/**
	 * Discards the internal cache.
	 */
	public function release(): void
	{
		$this->result = $this->count = $this->totalCount = NULL;
	}


	/********************* exporting ****************d*g**/


	/**
	 * Returns this data source wrapped in Fluent object.
	 */
	public function toFluent(): Fluent
	{
		return $this->connection->select('*')->from('(%SQL) t', $this->__toString());
	}


	/**
	 * Returns this data source wrapped in DataSource object.
	 */
	public function toDataSource(): DataSource
	{
		return new self($this->__toString(), $this->connection);
	}


	/**
	 * Returns SQL query.
	 */
	public function __toString(): string
	{
		try {
			return $this->connection->translate('
SELECT %n', (empty($this->cols) ? '*' : $this->cols), '
FROM %SQL', $this->sql, '
%ex', $this->conds ? ['WHERE %and', $this->conds] : NULL, '
%ex', $this->sorting ? ['ORDER BY %by', $this->sorting] : NULL, '
%ofs %lmt', $this->offset, $this->limit
			);
		} catch (\Throwable $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);
		}
	}


	/********************* counting ****************d*g**/


	/**
	 * Returns the number of rows in a given data source.
	 */
	public function count(): int
	{
		if ($this->count === NULL) {
			$this->count = $this->conds || $this->offset || $this->limit
				? (int) $this->connection->nativeQuery(
					'SELECT COUNT(*) FROM (' . $this->__toString() . ') t'
				)->fetchSingle()
				: $this->getTotalCount();
		}
		return $this->count;
	}


	/**
	 * Returns the number of rows in a given data source.
	 */
	public function getTotalCount(): int
	{
		if ($this->totalCount === NULL) {
			$this->totalCount = (int) $this->connection->nativeQuery(
				'SELECT COUNT(*) FROM ' . $this->sql
			)->fetchSingle();
		}
		return $this->totalCount;
	}

}
