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
 * Provides an interface between a dataset and data-aware components.
 * @package dibi
 * @deprecated
 */
interface IDataSource extends Countable, IteratorAggregate
{
	//function IteratorAggregate::getIterator();
	//function Countable::count();
}



/**
 * Default implementation of IDataSource for dibi.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 * @deprecated
 */
class DibiDataSource extends DibiObject implements IDataSource
{
	/** @var DibiConnection */
	private $connection;

	/** @var string */
	private $sql;

	/** @var int */
	private $count;



	/**
	 * @param  string  SQL command or table name, as data source
	 * @param  DibiConnection  connection
	 */
	public function __construct($sql, DibiConnection $connection = NULL)
	{
		if (strpos($sql, ' ') === FALSE) {
			// table name
			$this->sql = $sql;
		} else {
			// SQL command
			$this->sql = '(' . $sql . ') AS [source]';
		}

		$this->connection = $connection === NULL ? dibi::getConnection() : $connection;
	}



	/**
	 * @param  int  offset
	 * @param  int  limit
	 * @param  array columns
	 * @return ArrayIterator
	 */
	public function getIterator($offset = NULL, $limit = NULL)
	{
		return $this->connection->query('
			SELECT *
			FROM', $this->sql, '
			%ofs %lmt', $offset, $limit
		);
	}



	/**
	 * @return int
	 */
	public function count()
	{
		if ($this->count === NULL) {
			$this->count = $this->connection->query('
				SELECT COUNT(*) FROM', $this->sql
			)->fetchSingle();
		}
		return $this->count;
	}

}
