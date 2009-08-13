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
 * Default implementation of IDataSource for dibi.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiDataSource extends DibiObject implements IDataSource
{
	/** @var DibiConnection */
	private $connection;

	/** @var string */
	private $sql;

	/** @var DibiResult */
	private $result;

	/** @var int */
	private $count;

	/** @var int */
	private $totalCount;

	/** @var array */
	private $cols = array();

	/** @var array */
	private $sorting = array();

	/** @var array */
	private $conds = array();

	/** @var int */
	private $offset;

	/** @var int */
	private $limit;



	/**
	 * @param  string  SQL command or table or view name, as data source
	 * @param  DibiConnection  connection
	 */
	public function __construct($sql, DibiConnection $connection)
	{
		if (strpos($sql, ' ') === FALSE) {
			$this->sql = $connection->getDriver()->escape($sql, dibi::IDENTIFIER); // table name
		} else {
			$this->sql = '(' . $sql . ') t'; // SQL command
		}
		$this->connection = $connection;
	}



	/**
	 * Selects columns to query.
	 * @param  string|array  column name or array of column names
	 * @param  string  		 column alias
	 * @return DibiDataSource  provides a fluent interface
	 */
	public function select($col, $as = NULL)
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
	 * @return DibiDataSource  provides a fluent interface
	 */
	public function where($cond)
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
	 * @param  string  		 sorting direction
	 * @return DibiDataSource  provides a fluent interface
	 */
	public function orderBy($row, $sorting = 'ASC')
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
	 * @param  int limit
	 * @param  int offset
	 * @return DibiDataSource  provides a fluent interface
	 */
	public function applyLimit($limit, $offset = NULL)
	{
		$this->limit = $limit;
		$this->offset = $offset;
		$this->result = $this->count = NULL;
		return $this;
	}



	/**
	 * Returns the dibi connection.
	 * @return DibiConnection
	 */
	final public function getConnection()
	{
		return $this->connection;
	}



	/********************* executing ****************d*g**/



	/**
	 * Returns (and queries) DibiResult.
	 * @return DibiResult
	 */
	public function getResult()
	{
		if ($this->result === NULL) {
			$this->result = $this->connection->nativeQuery($this->__toString());
		}
		return $this->result;
	}



	/**
	 * @return DibiResultIterator
	 */
	public function getIterator()
	{
		return $this->getResult()->getIterator();
	}



	/**
	 * Generates, executes SQL query and fetches the single row.
	 * @return DibiRow|FALSE  array on success, FALSE if no next record
	 */
	public function fetch()
	{
		return $this->getResult()->fetch();
	}



	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed  value on success, FALSE if no next record
	 */
	public function fetchSingle()
	{
		return $this->getResult()->fetchSingle();
	}



	/**
	 * Fetches all records from table.
	 * @return array
	 */
	public function fetchAll()
	{
		return $this->getResult()->fetchAll();
	}



	/**
	 * Fetches all records from table and returns associative tree.
	 * @param  string  associative descriptor
	 * @return array
	 */
	public function fetchAssoc($assoc)
	{
		return $this->getResult()->fetchAssoc($assoc);
	}



	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @param  string  associative key
	 * @param  string  value
	 * @return array
	 */
	public function fetchPairs($key = NULL, $value = NULL)
	{
		return $this->getResult()->fetchPairs($key, $value);
	}



	/**
	 * Discards the internal cache.
	 * @return void
	 */
	public function release()
	{
		$this->result = $this->count = $this->totalCount = NULL;
	}



	/********************* exporting ****************d*g**/



	/**
	 * Returns this data source wrapped in DibiFluent object.
	 * @return DibiFluent
	 */
	public function toFluent()
	{
		return $this->connection->select('*')->from('(%SQL) AS t', $this->__toString());
	}



	/**
	 * Returns this data source wrapped in DibiDataSource object.
	 * @return DibiDataSource
	 */
	public function toDataSource()
	{
		return new self($this->__toString(), $this->connection);
	}



	/**
	 * Returns SQL query.
	 * @return string
	 */
	final public function __toString()
	{
		return $this->connection->sql('
			SELECT %n', (empty($this->cols) ? '*' : $this->cols), '
			FROM %SQL', $this->sql, '
			%ex', $this->conds ? array('WHERE %and', $this->conds) : NULL, '
			%ex', $this->sorting ? array('ORDER BY %by', $this->sorting) : NULL, '
			%ofs %lmt', $this->offset, $this->limit
		);
	}



	/********************* counting ****************d*g**/



	/**
	 * Returns the number of rows in a given data source.
	 * @return int
	 */
	public function count()
	{
		if ($this->count === NULL) {
			$this->count = $this->conds || $this->offset || $this->limit
				? (int) $this->connection->nativeQuery(
					'SELECT COUNT(*) FROM (' . $this->__toString() . ') AS t'
				)->fetchSingle()
				: $this->getTotalCount();
		}
		return $this->count;
	}



	/**
	 * Returns the number of rows in a given data source.
	 * @return int
	 */
	public function getTotalCount()
	{
		if ($this->totalCount === NULL) {
			$this->totalCount = (int) $this->connection->nativeQuery(
				'SELECT COUNT(*) FROM ' . $this->sql
			)->fetchSingle();
		}
		return $this->totalCount;
	}

}
