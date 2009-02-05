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
 * @version    $Id$
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

	/** @var int */
	private $count;

	/** @var array */
	private $cols = array();

	/** @var array */
	private $sorting = array();

	/** @var array */
	private $conds = array();



	/**
	 * @param  string  SQL command or table name, as data source
	 * @param  DibiConnection  connection
	 */
	public function __construct($sql, DibiConnection $connection = NULL)
	{
		$this->sql = $sql;
		$this->connection = $connection === NULL ? dibi::getConnection() : $connection;
	}



	/**
	 * @param  int  offset
	 * @param  int  limit
	 * @param  array columns
	 * @return DibiResultIterator
	 */
	public function getIterator($offset = NULL, $limit = NULL)
	{
		return $this->connection->query('
			SELECT %n', (empty($this->cols) ? '*' : $this->cols), '
			FROM (%SQL) AS [t]', $this->sql, '
			WHERE %and', $this->conds, '
			ORDER BY %by', $this->sorting, '
			%ofs %lmt', $offset, $limit
		)->getIterator();
	}



	/**
	 * @return int
	 */
	public function count()
	{
		if ($this->count === NULL) {
			$this->count = $this->connection->nativeQuery(
				'SELECT COUNT(*) FROM (' . $this->sql . ') AS t'
			)->fetchSingle();
		}
		return $this->count;
	}




	/**
	 * Returns SQL wrapped as DibiFluent.
	 * @return DibiFluent
	 * @throws DibiException
	 */
	public function toFluent()
	{
		return $this->connection->select('*')->from('(%SQL) AS [t]', $this->__toString());
	}



	/**
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
			FROM (%SQL) AS [t]', $this->sql, '
			WHERE %and', $this->conds, '
			ORDER BY %by', $this->sorting, '
		');
	}



	/**
	 */
	public function select($col, $as = NULL)
	{
		if (is_array($col)) {
			$this->cols = $col;
		} else {
			$this->cols[$col] = $as;
		}
	}



	/**
	 */
	public function where($cond)
	{
		if (is_array($cond)) {
			// TODO: not consistent with select and orderBy
			$this->conds[] = $cond;
		} else {
			$this->conds[] = func_get_args();
		}
	}



	/**
	 */
	public function orderBy($row, $sorting = 'ASC')
	{
		if (is_array($row)) {
			$this->sorting = $row;
		} else {
			$this->sorting[$row] = $sorting;
		}
	}



	/**
	 * Returns the dibi connection.
	 * @return DibiConnection
	 */
	final public function getConnection()
	{
		return $this->connection;
	}

}
