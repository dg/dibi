<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com
 *
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 * @version    $Id$
 */


/**
 * Experimental object-oriented interface to database tables.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 */
abstract class DibiTable extends DibiObject
{
	/** @var string  primary key mask */
	public static $primaryMask = 'id';

	/** @var bool */
	public static $lowerCase = TRUE;

	/** @var DibiConnection */
	private $connection;

	/** @var string  table name */
	protected $name;

	/** @var string  primary key name */
	protected $primary;

	/** @var string  primary key type */
	protected $primaryModifier = '%i';

	/** @var bool  primary key is auto increment */
	protected $primaryAutoIncrement = TRUE;

	/** @var array */
	protected $blankRow = array();

	/** @var array of pairs [type, format] */
	protected $types = array();


	/**
	 * Table constructor.
	 * @param  DibiConnection
	 * @return void
	 */
	public function __construct(DibiConnection $connection = NULL)
	{
		$this->connection = $connection === NULL ? dibi::getConnection() : $connection;
		$this->setup();
	}



	/**
	 * Returns the table name.
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * Returns the primary key name.
	 * @return string
	 */
	public function getPrimary()
	{
		return $this->primary;
	}



	/**
	 * Returns the dibi connection.
	 * @return DibiConnection
	 */
	public function getConnection()
	{
		return $this->connection;
	}



	/**
	 * Setup object.
	 * @return void
	 */
	protected function setup()
	{
		// autodetect table name
		if ($this->name === NULL) {
			$name = $this->getClass();
			if (FALSE !== ($pos = strrpos($name, ':'))) {
				$name = substr($name, $pos + 1);
			}
			if (self::$lowerCase) {
				$name = strtolower($name);
			}
			$this->name = $name;
		}

		// autodetect primary key name
		if ($this->primary === NULL) {
			$this->primary = str_replace(
				array('%p', '%s'),
				array($this->name, trim($this->name, 's')), // the simplest inflector in the world :-))
				self::$primaryMask
			);
		}
	}



	/********************* basic commands ****************d*g**/



	/**
	 * Inserts row into a table.
	 * @param  array|object
	 * @return int  new primary key
	 */
	public function insert($data)
	{
		$this->connection->query(
			'INSERT INTO %n', $this->name, '%v', $this->prepare($data)
		);

		if ($this->primaryAutoIncrement) {
			return $this->connection->insertId();
		}
	}



	/**
	 * Updates rows in a table.
	 * @param  mixed  primary key value(s)
	 * @param  array|object
	 * @return int    number of updated rows
	 */
	public function update($where, $data)
	{
		$data = $this->prepare($data);
		if ($where === NULL && isset($data[$this->primary])) {;
			$where = $data[$this->primary];
			unset($data[$this->primary]);
		}

		$this->connection->query(
			'UPDATE %n', $this->name,
			'SET %a', $data,
			'WHERE %n', $this->primary, 'IN (' . $this->primaryModifier, $where, ')'
		);
		return $this->connection->affectedRows();
	}



	/**
	 * Inserts or updates rows in a table.
	 * @param  array|object
	 * @return int    (new) primary key
	 */
	public function insertOrUpdate($data)
	{
		$data = $this->prepare($data);
		if (!isset($data[$this->primary])) {
			throw new InvalidArgumentException("Missing primary key '$this->primary' in dataset.");
		}

		try {
			$this->connection->query(
				'INSERT INTO %n', $this->name, '%v', $data
			);

		} catch (DibiDriverException $e) {
			$where = $data[$this->primary];
			unset($data[$this->primary]);
			$this->connection->query(
				'UPDATE %n', $this->name,
				'SET %a', $data,
				'WHERE %n', $this->primary, 'IN (' . $this->primaryModifier, $where, ')'
			);
		}
	}



	/**
	 * Deletes rows from a table by primary key.
	 * @param  mixed  primary key value(s)
	 * @return int    number of deleted rows
	 */
	public function delete($where)
	{
		$this->connection->query(
			'DELETE FROM %n', $this->name,
			'WHERE %n', $this->primary, 'IN (' . $this->primaryModifier, $where, ')'
		);
		return $this->connection->affectedRows();
	}



	/**
	 * Finds rows by primary key.
	 * @param  mixed  primary key value(s)
	 * @return DibiResult
	 */
	public function find($what)
	{
		if (!is_array($what)) {
			$what = func_get_args();
		}
		return $this->complete($this->connection->query(
			'SELECT * FROM %n', $this->name,
			'WHERE %n', $this->primary, 'IN (' . $this->primaryModifier, $what, ')'
		));
	}



	/**
	 * Selects all rows.
	 * @param  array  conditions
	 * @param  string|array  column to order by
	 * @return DibiResult
	 */
	public function findAll($conditions = NULL, $order = NULL)
	{
		if (!is_array($order)) {
			$order = func_get_args();
			if (is_array($conditions)) {
				array_shift($order);
			} else {
				$conditions = NULL;
			}	
		}
		return $this->complete($this->connection->query(
			'SELECT * FROM %n', $this->name,
			'%ex', $conditions ? array('WHERE %and', $conditions) : NULL,
			'%ex', $order ? array('ORDER BY %by', $order) : NULL
		));
	}



	/**
	 * Fetches single row.
	 * @param  scalar|array  primary key value
	 * @return array|object row
	 */
	public function fetch($conditions)
	{
		if (is_array($conditions)) {
			return $this->complete($this->connection->query(
				'SELECT * FROM %n', $this->name,
				'WHERE %and', $conditions
			))->fetch();
		}
		return $this->complete($this->connection->query(
			'SELECT * FROM %n', $this->name,
			'WHERE %n=' . $this->primaryModifier, $this->primary, $conditions
		))->fetch();
	}



	/**
	 * Returns a blank row (not fetched from database).
	 * @return array|object
	 */
	public function createBlank()
	{
		$row = $this->blankRow;
		$row[$this->primary] = NULL;

		if ($class = $this->connection->getConfig('resultObjects')) {
			if ($class === TRUE) {
				$row = (object) $row;
			} else {
				$row = new $class($row);
			}
		}

		return $row;
	}



	/**
	 * User data pre-processing.
	 * @param  array|object
	 * @return array
	 */
	protected function prepare($data)
	{
		if (is_object($data)) {
			return (array) $data;
		} elseif (is_array($data)) {
			return $data;
		}

		throw new InvalidArgumentException('Dataset must be array or anonymous object.');
	}



	/**
	 * User DibiResult post-processing.
	 * @param  DibiResult
	 * @return DibiResult
	 */
	protected function complete($res)
	{
		$res->setTypes($this->types);
		return $res;
	}



	/********************* fluent SQL builders ****************d*g**/



	/**
	 * Creates fluent SQL builder.
	 * @return DibiFluent
	 */
	public function command()
	{
		return new DibiFluent($this->connection);
	}



	/**
	 * @param  string    column name
	 * @return DibiFluent
	 */
	public function select($args)
	{
		$args = func_get_args();
		return $this->command()->__call('select', $args)->from($this->name);
	}

}
